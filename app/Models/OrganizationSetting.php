<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

class OrganizationSetting extends Model
{
    public const DEFAULT_COLOR = '#1f6f5c';

    /** Logo and icon paths are set by code only, never from a form. */
    protected $fillable = [
        'name', 'membership_prefix', 'site_title', 'tagline', 'primary_color',
        'phone', 'email', 'address', 'address_secondary', 'working_hours', 'whatsapp',
        'facebook_url', 'instagram_url', 'youtube_url', 'ticker_label', 'ticker_posts',
    ];

    /**
     * The single settings row. Falls back to defaults if the table is not
     * created yet (for example before "php artisan migrate" has been run),
     * so pages never crash because of missing settings.
     */
    public static function current(): self
    {
        try {
            return static::query()->first() ?? new static(static::defaults());
        } catch (QueryException) {
            return new static(static::defaults());
        }
    }

    public static function defaults(): array
    {
        return ['name' => 'Lizer Media', 'membership_prefix' => 'LM'];
    }

    /** First letters of the first two words, e.g. "Lizer Media" becomes "LM". */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

        $letters = array_map(
            fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)),
            array_slice($words ?: [], 0, 2)
        );

        return implode('', $letters) ?: 'N';
    }

    /** tel: link for the phone number, or null. */
    public function phoneHref(): ?string
    {
        $digits = preg_replace('/[^0-9+]/', '', (string) $this->phone);

        return $digits ? 'tel:' . $digits : null;
    }

    /** wa.me chat link: the WhatsApp number, or the phone number when none is set. */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) ($this->whatsapp ?: $this->phone));

        return $digits ? 'https://wa.me/' . $digits : null;
    }

    /** Text for the browser tab and page titles. */
    public function displayTitle(): string
    {
        return filled($this->site_title) ? $this->site_title : (string) $this->name;
    }

    public function primaryColor(): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->primary_color) ? strtolower($this->primary_color) : self::DEFAULT_COLOR;
    }

    /** A darker shade of the main colour, used for the sidebar and hover states. */
    public function primaryDark(): string
    {
        [$r, $g, $b] = sscanf($this->primaryColor(), '#%02x%02x%02x');

        return sprintf('#%02x%02x%02x', (int) round($r * 0.78), (int) round($g * 0.78), (int) round($b * 0.78));
    }

    public function hasLogo(): bool
    {
        return $this->fileExists($this->logo_path);
    }

    public function hasFavicon(): bool
    {
        return $this->fileExists($this->favicon_path);
    }

    /** Address of the logo for web pages (the version number refreshes browsers after a change). */
    public function logoUrl(): ?string
    {
        return $this->hasLogo() ? route('site.logo', ['v' => $this->updated_at?->timestamp], false) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->hasFavicon() ? route('site.icon', ['v' => $this->updated_at?->timestamp], false) : null;
    }

    /** File on disk, for PDFs. */
    public function logoFilePath(): ?string
    {
        return $this->hasLogo() ? Storage::disk('local')->path($this->logo_path) : null;
    }

    private function fileExists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::disk('local')->exists($path);
    }
}
