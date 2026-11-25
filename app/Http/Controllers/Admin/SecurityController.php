<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Security permissions: switch each admin area on or off for the Coordinator role.
 * Super admin only (route middleware). Uses the same rules as the roles screen.
 */
class SecurityController extends Controller
{
    public function edit(): View
    {
        $role = $this->coordinatorRole();
        $granted = $role->permissions()->pluck('permissions.id')->all();

        return view('admin.security.edit', [
            'groups' => $this->groups(),
            'granted' => $granted,
            'role' => $role,
        ]);
    }

    public function update(Request $request, RolePermissionService $service): RedirectResponse
    {
        $role = $this->coordinatorRole();

        $selected = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['integer']])['permissions'] ?? [];

        $allowed = Permission::query()->whereIn('name', array_keys(config('admin.permissions', [])))->pluck('id')->all();
        $ids = array_values(array_intersect(array_map('intval', $selected), $allowed));

        $role->permissions()->sync($service->idsFor($ids, $role));

        return redirect()->route('security.edit')->with('success', 'Coordinator security permissions saved. Changes apply on their next request.');
    }

    private function coordinatorRole(): Role
    {
        return Role::query()->where('name', 'coordinator')->firstOrFail();
    }

    /** Groups in the order of config/admin.php, with the permission rows loaded from the database. */
    private function groups(): array
    {
        $rows = Permission::query()->whereIn('name', array_keys(config('admin.permissions', [])))->get()->keyBy('name');
        $groups = [];

        foreach (config('admin.permissions', []) as $name => [$label, $group]) {
            if ($row = $rows->get($name)) {
                $groups[$group][] = ['id' => $row->id, 'label' => $label];
            }
        }

        return $groups;
    }
}
