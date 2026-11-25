<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    public const UPDATED_AT = null;   // rows are written once and never changed

    public const EVENT_LOGIN = 'login';
    public const EVENT_FAILED = 'failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_LOCKOUT = 'lockout';

    public const EVENTS = [
        self::EVENT_LOGIN,
        self::EVENT_FAILED,
        self::EVENT_LOGOUT,
        self::EVENT_LOCKOUT,
    ];

    public const LABELS = [
        self::EVENT_LOGIN => 'Logged in',
        self::EVENT_FAILED => 'Failed attempt',
        self::EVENT_LOGOUT => 'Logged out',
        self::EVENT_LOCKOUT => 'Locked out',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->event] ?? $this->event;
    }

    /** A short, readable browser and system name, e.g. "Chrome on Windows". */
    public function browser(): string
    {
        $agent = (string) $this->user_agent;

        if ($agent === '') {
            return 'Unknown';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Other browser',
        };

        $system = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} on {$system}" : $browser;
    }
}
