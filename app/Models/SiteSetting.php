<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\QueryException;

/**
 * Key/value settings. Use SiteSetting::get('seo.title') and SiteSetting::put('seo.title', $value).
 * Values flagged as secret (SMTP password, gateway secrets) are encrypted at rest.
 */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];

    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $row = static::query()->where('key', $key)->first();
        } catch (QueryException) {
            return $default;
        }

        if (! $row || $row->value === null || $row->value === '') {
            return $default;
        }

        if ($row->is_secret) {
            try {
                return Crypt::decryptString($row->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $row->value;
    }

    public static function put(string $key, ?string $value, bool $secret = false): void
    {
        $stored = ($secret && filled($value)) ? Crypt::encryptString($value) : $value;

        static::query()->updateOrCreate(['key' => $key], ['value' => $stored, 'is_secret' => $secret]);
    }

    /** Stores a non-empty secret only; an empty value keeps the existing secret. */
    public static function putSecretIfFilled(string $key, ?string $value): void
    {
        if (filled($value)) {
            static::put($key, $value, true);
        }
    }

    public static function allWithPrefix(string $prefix): array
    {
        $out = [];

        foreach (static::query()->where('key', 'like', $prefix . '%')->get() as $row) {
            $out[$row->key] = $row->is_secret ? static::get($row->key) : $row->value;
        }

        return $out;
    }
}
