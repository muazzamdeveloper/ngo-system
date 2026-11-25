<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteLink extends Model
{
    public const GROUPS = [
        'nav' => 'Header menu',
        'header_action' => 'Header buttons',
        'quick' => 'Quick links',
        'action' => 'Action buttons',
        'legal' => 'Legal links (bottom bar)',
    ];

    protected $fillable = ['group', 'label', 'url', 'new_tab', 'is_active', 'sort_order'];

    /** Open in a new tab: when asked for, and always for other websites. */
    public function opensInNewTab(): bool
    {
        return $this->new_tab || str_starts_with($this->url, 'http');
    }

    /** Is this link the page being viewed? */
    public function isCurrent(): bool
    {
        $path = trim((string) parse_url($this->url, PHP_URL_PATH), '/');

        return $path === '' ? request()->path() === '/' : request()->is($path, $path . '/*');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'new_tab' => 'boolean'];
    }
}
