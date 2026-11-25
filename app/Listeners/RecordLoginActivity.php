<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Throwable;

/**
 * Writes one row per sign-in event. Passwords are never stored, and a problem
 * here must never stop someone from logging in, so every write is guarded.
 */
class RecordLoginActivity
{
    public function onLogin(Login $event): void
    {
        $this->record(LoginActivity::EVENT_LOGIN, $event->user?->getAuthIdentifier(), $event->user->email ?? null);
    }

    public function onFailed(Failed $event): void
    {
        $this->record(
            LoginActivity::EVENT_FAILED,
            $event->user?->getAuthIdentifier(),
            $event->credentials['email'] ?? null,   // only the email; the password in $credentials is ignored
        );
    }

    public function onLogout(Logout $event): void
    {
        $this->record(LoginActivity::EVENT_LOGOUT, $event->user?->getAuthIdentifier(), $event->user->email ?? null);
    }

    public function onLockout(Lockout $event): void
    {
        $this->record(LoginActivity::EVENT_LOCKOUT, null, $event->request->input('email'));
    }

    private function record(string $event, mixed $userId, mixed $email): void
    {
        try {
            $request = request();

            LoginActivity::create([
                'user_id' => $userId,
                'email' => $this->clean($email, 255),
                'event' => $event,
                'ip_address' => $this->clean($request->ip(), 45),
                'user_agent' => $this->clean($request->userAgent(), 255),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function clean(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }
}
