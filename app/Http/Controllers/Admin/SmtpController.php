<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\MailSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class SmtpController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('email.manage');

        return view('admin.smtp.edit', [
            'host' => SiteSetting::get('smtp.host'),
            'port' => SiteSetting::get('smtp.port', '587'),
            'username' => SiteSetting::get('smtp.username'),
            'fromAddress' => SiteSetting::get('smtp.from_address'),
            'fromName' => SiteSetting::get('smtp.from_name'),
            'encryption' => SiteSetting::get('smtp.encryption', 'tls'),
            'hasPassword' => filled(SiteSetting::get('smtp.password')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('email.manage');

        $data = $request->validate([
            'host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'encryption' => ['required', 'in:tls,ssl,none'],
        ]);

        SiteSetting::put('smtp.host', $data['host']);
        SiteSetting::put('smtp.port', (string) $data['port']);
        SiteSetting::put('smtp.username', $data['username'] ?? null);
        SiteSetting::put('smtp.from_address', $data['from_address']);
        SiteSetting::put('smtp.from_name', $data['from_name'] ?? null);
        SiteSetting::put('smtp.encryption', $data['encryption']);
        // An empty password box keeps the saved password.
        SiteSetting::putSecretIfFilled('smtp.password', $data['password'] ?? null);

        return back()->with('success', 'SMTP settings saved.');
    }

    public function test(Request $request): RedirectResponse
    {
        Gate::authorize('email.manage');

        $data = $request->validate(['recipient' => ['required', 'email', 'max:190']]);

        if (! MailSender::smtpConfigured()) {
            return back()->with('error', 'Save the SMTP host and from address first.');
        }

        try {
            MailSender::mailer(true)->raw(
                "This is a test message from the NGO admin panel. If you received it, SMTP is working.",
                fn ($message) => $message->to($data['recipient'])->subject('SMTP test - NGO admin panel')
                    ->from(SiteSetting::get('smtp.from_address'), SiteSetting::get('smtp.from_name')),
            );
        } catch (Throwable $e) {
            return back()->with('error', 'The test email could not be sent. Check the host, port, encryption and password.');
        }

        return back()->with('success', 'Test email sent to ' . $data['recipient'] . '.');
    }
}
