<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Support\Facades\Route;

/**
 * Builds the admin sidebar from config/admin.php. Only items the user may open are returned;
 * a group with no visible items is left out.
 */
class AdminNav
{
    public static function build(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $groups = [];

        foreach (config('admin.nav', []) as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (! self::visible($item, $user) || ! self::routeExists($item)) {
                    continue;
                }

                $href = isset($item['module'])
                    ? route('modules.index', $item['module'])
                    : route($item['route']);

                $items[] = [
                    'label' => $item['label'],
                    'href' => $href,
                    'active' => self::isActive($item),
                    'badge' => self::badge($item['badge'] ?? null),
                    'badge_key' => $item['badge'] ?? null,
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'label' => $group['label'],
                'icon' => $group['icon'] ?? 'grid',
                'items' => $items,
                'open' => collect($items)->contains('active', true),
            ];
        }

        return $groups;
    }

    private static function visible(array $item, User $user): bool
    {
        if (! empty($item['always'])) {
            return true;
        }

        if (! empty($item['super'])) {
            return $user->isSuperAdmin();
        }

        $permission = $item['permission'] ?? (isset($item['module']) ? config("admin.modules.{$item['module']}.permission") : null);

        return $permission !== null && $user->can($permission);
    }

    private static function routeExists(array $item): bool
    {
        return isset($item['module']) || Route::has($item['route']);
    }

    private static function isActive(array $item): bool
    {
        if (isset($item['module'])) {
            return request()->route('module') === $item['module'];
        }

        if (! empty($item['match'])) {
            return request()->routeIs($item['match']);
        }

        return false;
    }

    private static function badge(?string $name): int
    {
        try {
            return match ($name) {
                'applications' => Member::query()->online()->where('status', 'pending')->count(),
                'payments' => Member::query()->whereNull('payment_status')->whereNotNull('payment_screenshot_path')->count(),
                'volunteers' => Volunteer::query()->where('status', 'pending')->count(),
                default => 0,
            };
        } catch (\Throwable) {
            return 0;
        }
    }
}
