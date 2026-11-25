<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailNotification;
use App\Models\Member;
use App\Services\MailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Compose and send announcements to members or applicants, with a history of what was sent. */
class NotificationController extends Controller
{
    private const MAX_RECIPIENTS = 2000;

    public function index(): View
    {
        Gate::authorize('email.manage');

        return view('admin.notifications.index', [
            'history' => EmailNotification::query()->with('sender')->latest()->paginate(10),
            'totals' => [
                'sent' => (int) EmailNotification::query()->sum('recipients'),
                'delivered' => (int) EmailNotification::query()->sum('sent_count'),
                'failed' => (int) EmailNotification::query()->sum('failed_count'),
                'audience' => $this->recipientsFor('approved_members')->count(),
            ],
            'categories' => EmailNotification::CATEGORIES,
            'audiences' => EmailNotification::AUDIENCES,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        Gate::authorize('email.manage');

        $data = $request->validate([
            'audience' => ['required', 'in:' . implode(',', array_keys(EmailNotification::AUDIENCES))],
            'category' => ['required', 'in:' . implode(',', array_keys(EmailNotification::CATEGORIES))],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'action_url' => ['nullable', 'url:http,https', 'max:500'],
            'via_smtp' => ['nullable', 'boolean'],
        ]);

        $addresses = $this->recipientsFor($data['audience'])->limit(self::MAX_RECIPIENTS)->pluck('email')->all();

        if ($addresses === []) {
            return back()->withInput()->with('error', 'No recipients with an email address in this audience.');
        }

        $body = $data['body'] . (filled($data['action_url'] ?? null) ? "\n\n" . $data['action_url'] : '');
        $viaSmtp = $request->boolean('via_smtp');

        [$sent, $failed] = MailSender::send($addresses, $data['subject'], $body, $viaSmtp);

        EmailNotification::create([
            'subject' => $data['subject'],
            'body' => $data['body'],
            'category' => $data['category'],
            'audience' => $data['audience'],
            'action_url' => $data['action_url'] ?? null,
            'via_smtp' => $viaSmtp,
            'recipients' => count($addresses),
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sent_by' => $request->user()->id,
        ]);

        $note = count($addresses) === self::MAX_RECIPIENTS ? ' (limited to the first ' . self::MAX_RECIPIENTS . ' recipients)' : '';

        return redirect()->route('notifications.index')->with('success', "Sent to {$sent} recipient(s), {$failed} failed{$note}.");
    }

    private function recipientsFor(string $audience)
    {
        $query = Member::query()->whereNotNull('email')->where('email', '!=', '');

        return $audience === 'pending_members'
            ? $query->where('status', 'pending')
            : $query->where('status', 'approved');
    }
}
