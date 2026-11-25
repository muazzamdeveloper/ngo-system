<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateOwnProfileRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** A member's own profile. There is no id in any URL: it is always the logged-in person's record. */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->can('portal.edit_profile'), 403, 'Editing your details is turned off for your account.');

        return view('account.profile-edit', ['member' => $this->ownMember($request)]);
    }

    public function update(UpdateOwnProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()->can('portal.edit_profile'), 403, 'Editing your details is turned off for your account.');

        $member = $this->ownMember($request);
        $data = $request->validated();
        $oldPhoto = $member->photo_path;

        $newPhoto = $request->hasFile('photo')
            ? $request->file('photo')->store('members/photos', 'local')
            : null;

        try {
            DB::transaction(function () use ($member, $data, $newPhoto, $request) {
                $member->fill($data);

                if ($newPhoto) {
                    $member->photo_path = $newPhoto;
                } elseif ($request->boolean('remove_photo')) {
                    $member->photo_path = null;
                }

                $member->save();
            });
        } catch (Throwable $e) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto);
            }

            throw $e;
        }

        if ($oldPhoto && $oldPhoto !== $member->photo_path) {
            Storage::disk('local')->delete($oldPhoto);
        }

        Log::info('Member updated their own profile', ['member_id' => $member->id]);

        return redirect()->route('account.home')->with('success', 'Your details were updated.');
    }

    public function photo(Request $request): StreamedResponse
    {
        $member = $this->ownMember($request);

        abort_unless($member->photo_path && Storage::disk('local')->exists($member->photo_path), 404);

        return Storage::disk('local')->response($member->photo_path, null, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ownMember(Request $request): Member
    {
        return Member::where('user_id', $request->user()->id)->firstOrFail();
    }
}
