<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/** Turns a config/site_nav.php entry into an href and an active flag. */
class SiteNav
{
    /** @return array{href: string, active: bool, external: bool} */
    public static function link(array $item): array
    {
        if (isset($item['route'])) {
            $route = $item['route'];
            $params = isset($item['param']) ? [$item['param']] : [];
            $href = Route::has($route) ? route($route, $params) : '#';
            $active = Route::has($route) && request()->routeIs($route);
        } else {
            $href = $item['url'] ?? '#';
            $active = $href !== '#' && $href !== '/' && request()->path() === ltrim($href, '/');
        }

        return ['href' => $href, 'active' => $active, 'external' => str_starts_with($href, 'http') && ! str_starts_with($href, url('/'))];
    }

    /** True when any child of a dropdown is the current page. */
    public static function anyChildActive(array $children): bool
    {
        foreach ($children as $child) {
            if (self::link($child)['active']) {
                return true;
            }
        }

        return false;
    }
}
