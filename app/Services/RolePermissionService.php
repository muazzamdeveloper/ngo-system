<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;

class RolePermissionService
{
    /**
     * Turn the ticked checkboxes into the permission ids to save.
     *
     * - Super-admin-only permissions are always ignored, even if someone sends them.
     * - The Member role can only hold "Member portal" permissions (never admin access);
     *   every other role can never hold them.
     * - A role with any permission also needs admin.access, otherwise it could not use the panel.
     * - Any members.* permission also needs members.view, otherwise there is nothing to act on.
     *
     * @param  array<int, int|string>  $selectedIds
     * @return array<int, int>
     */
    public function idsFor(array $selectedIds, ?Role $role = null): array
    {
        $scope = $role ? Permission::forRole($role) : Permission::assignable()->where('name', 'not like', Permission::PORTAL_PREFIX . '%');

        // Member role: just the portal permissions that were ticked, nothing added.
        if ($role?->isMemberRole()) {
            return $scope->whereIn('id', $selectedIds)->pluck('id')->all();
        }

        $names = (clone $scope)->whereIn('id', $selectedIds)->pluck('name')->all();

        if ($names === []) {
            return [];
        }

        $names[] = 'admin.access';

        foreach ($names as $name) {
            if (str_starts_with($name, 'members.')) {
                $names[] = 'members.view';
                break;
            }
        }

        return $scope->whereIn('name', array_unique($names))->pluck('id')->all();
    }
}
