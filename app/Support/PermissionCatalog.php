<?php

namespace App\Support;

use App\Models\Permission;

/**
 * Every permission a staff account can be given, grouped for the checkbox screens.
 * Super-admin-only permissions and member-portal permissions are left out.
 */
class PermissionCatalog
{
    /** @return array<string, array<int, array{id:int, label:string}>> */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::query()->orderBy('group')->orderBy('label')->get() as $row) {
            $groups[$row->group ?: 'Other'][] = ['id' => $row->id, 'label' => $row->label];
        }

        return $groups;
    }

    /** Ids of every permission a staff account may hold (drops anything else that is posted). */
    public static function allowedIds(): array
    {
        return self::query()->pluck('id')->all();
    }

    private static function query()
    {
        return Permission::query()
            ->assignable()
            ->where('name', 'not like', Permission::PORTAL_PREFIX . '%');
    }
}
