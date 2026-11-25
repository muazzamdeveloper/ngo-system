<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exceptions\InvalidMemberTransition;
use App\Http\Requests\Admin\StoreMemberRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\Member;
use App\Services\MemberDocumentService;
use App\Services\MemberStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MemberController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $search = mb_substr($search, 0, 100);

        // An unknown status in the URL is simply ignored.
        $status = $request->query('status');
        $status = in_array($status, Member::STATUSES, true) ? $status : null;

        $members = Member::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                // Escape % and _ so the visitor's text is matched literally.
                $like = '%' . addcslashes($search, '\\%_') . '%';

                $query->where(function ($query) use ($like) {
                    $query->where('full_name', 'like', $like)
                        ->orWhere('father_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('membership_number', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = Member::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.members.index', [
            'members' => $members,
            'search' => $search,
            'status' => $status,
            'counts' => $counts,
            'totalCount' => $counts->sum(),
        ]);
    }

    public function create(): View
    {
        return view('admin.members.create', ['genders' => Member::GENDERS, 'member' => null, 'editing' => false]);
    }

    public function store(StoreMemberRequest $request, MemberDocumentService $documents): RedirectResponse
    {
        $data = $request->validated();

        // Files go to the private disk (storage/app/private), never to a public folder.
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('members/photos', 'local')
            : null;
        $documentPaths = $documents->storeUploads($request);

        try {
            $member = DB::transaction(function () use ($data, $photoPath, $documentPaths, $request) {
                $member = new Member();
                $member->fill($data);
                $member->photo_path = $photoPath;
                $member->forceFill($documentPaths);
                $member->setAadhaar($data['aadhaar'] ?? null);
                $member->created_by = $request->user()->id;
                $member->source = Member::SOURCE_ADMIN;
                $member->save(); // status defaults to "pending"

                return $member;
            });
        } catch (Throwable $e) {
            $documents->delete([$photoPath, ...array_values($documentPaths)]);

            throw $e;
        }

        return redirect()
            ->route('members.index')
            ->with('success', "Member {$member->full_name} was added with status Pending.");
    }

    /** Streams a member photo through the application so the file is never publicly reachable. */
    public function photo(Member $member): StreamedResponse
    {
        abort_unless($member->photo_path && Storage::disk('local')->exists($member->photo_path), 404);

        return Storage::disk('local')->response($member->photo_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function show(Member $member): View
    {
        return view('admin.members.show', $this->showData($member, null));
    }

    /**
     * Shows the full Aadhaar number for this one response only. It needs its own
     * permission, is never cached, and each view is written to the log (without the number).
     */
    public function revealAadhaar(Request $request, Member $member): Response
    {
        Log::info('Aadhaar number viewed', ['member_id' => $member->id, 'user_id' => $request->user()->id]);

        return response()
            ->view('admin.members.show', $this->showData($member, $member->fullAadhaar()))
            ->header('Cache-Control', 'no-store, private');
    }

    public function edit(Member $member): View
    {
        return view('admin.members.edit', [
            'member' => $member,
            'genders' => Member::GENDERS,
            'editing' => true,
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member, MemberDocumentService $documents): RedirectResponse
    {
        $data = $request->validated();

        $newPhoto = $request->hasFile('photo')
            ? $request->file('photo')->store('members/photos', 'local')
            : null;
        $newDocuments = $documents->storeUploads($request);   // column => new path

        // Work out which stored files will be replaced or removed.
        $changes = $newDocuments;
        foreach (Member::DOCUMENT_COLUMNS as $field => $column) {
            if (! isset($changes[$column]) && $request->boolean('remove_' . $field)) {
                $changes[$column] = null;
            }
        }

        $oldFiles = [];
        foreach (array_keys($changes) as $column) {
            $oldFiles[] = $member->{$column};
        }

        if ($newPhoto) {
            $oldFiles[] = $member->photo_path;
        } elseif ($request->boolean('remove_photo')) {
            $oldFiles[] = $member->photo_path;
        }

        try {
            DB::transaction(function () use ($member, $data, $newPhoto, $changes, $request) {
                $member->fill($data);
                $member->forceFill($changes);

                if ($newPhoto) {
                    $member->photo_path = $newPhoto;
                } elseif ($request->boolean('remove_photo')) {
                    $member->photo_path = null;
                }

                if (! empty($data['aadhaar'])) {
                    $member->setAadhaar($data['aadhaar']);
                }

                $member->save();
            });
        } catch (Throwable $e) {
            $documents->delete([$newPhoto, ...array_values($newDocuments)]);

            throw $e;
        }

        // Only after the change is saved are the replaced files removed.
        $documents->delete($oldFiles);

        Log::info('Member updated', ['member_id' => $member->id, 'user_id' => $request->user()->id]);

        return redirect()->route('members.show', $member)->with('success', 'Member details updated.');
    }

    /**
     * Streams one uploaded document through the application (files are never publicly reachable).
     * The Aadhaar images need the Aadhaar permission, and every view of them is logged.
     */
    public function document(Request $request, Member $member, string $document): StreamedResponse
    {
        $field = str_replace('-', '_', $document);
        $column = Member::DOCUMENT_COLUMNS[$field] ?? null;

        abort_if($column === null, 404);

        $isAadhaar = in_array($field, ['aadhaar_front', 'aadhaar_back'], true);

        if ($isAadhaar) {
            abort_unless($request->user()->can('members.view_aadhaar'), 403);
        }

        $path = $member->{$column};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        if ($isAadhaar) {
            Log::info('Aadhaar document viewed', ['member_id' => $member->id, 'document' => $field, 'user_id' => $request->user()->id]);
        }

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => $isAadhaar ? 'no-store, private' : 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'approve', 'Member approved. A membership number was assigned.');
    }

    public function reject(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'reject', 'Member rejected.');
    }

    public function reopen(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'reopen', 'Member reopened as pending.');
    }

    public function deactivate(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'deactivate', 'Member deactivated.');
    }

    public function restore(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'restore', 'Member restored.');
    }

    public function block(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'block', 'Member blocked.');
    }

    public function unblock(Request $request, Member $member, MemberStatusService $statuses): RedirectResponse
    {
        return $this->transition($request, $member, $statuses, 'unblock', 'Member unblocked.');
    }

    /** Runs one status action. Rejecting and blocking need a written reason. */
    private function transition(Request $request, Member $member, MemberStatusService $statuses, string $action, string $message): RedirectResponse
    {
        $noteRules = in_array($action, ['reject', 'block'], true)
            ? ['required', 'string', 'min:3', 'max:500']
            : ['nullable', 'string', 'max:500'];

        $note = $request->validate(['note' => $noteRules])['note'] ?? null;

        try {
            $statuses->{$action}($member, $request->user(), $note);
        } catch (InvalidMemberTransition $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('members.show', $member)->with('success', $message);
    }

    private function showData(Member $member, ?string $revealedAadhaar): array
    {
        $member->load(['creator', 'reviewer', 'user']);

        return ['member' => $member, 'revealedAadhaar' => $revealedAadhaar];
    }
}
