<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(),
            'groups' => $this->permissionGroups(new Role()),
            'selected' => [],
        ]);
    }

    public function store(StoreRoleRequest $request, RolePermissionService $permissions): RedirectResponse
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data, $permissions) {
            $role = Role::create([
                'name' => $this->uniqueName($data['label']),
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);

            $role->permissions()->sync($permissions->idsFor($data['permissions'] ?? []));

            return $role;
        });

        Log::info('Role created', ['role' => $role->name, 'user_id' => $request->user()->id]);

        return redirect()->route('roles.index')->with('success', "Role {$role->label} was created.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role,
            'groups' => $this->permissionGroups($role),
            'selected' => $role->permissions()->pluck('permissions.id')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, RolePermissionService $permissions): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($role, $data, $permissions) {
            $role->update([
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
            ]);

            $role->permissions()->sync($permissions->idsFor($data['permissions'] ?? [], $role));
        });

        Log::info('Role updated', ['role' => $role->name, 'user_id' => $request->user()->id]);

        return redirect()->route('roles.index')->with('success', "Role {$role->label} was updated.");
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'System roles cannot be deleted.');

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role still has users. Move them to another role first.']);
        }

        $role->delete();

        Log::info('Role deleted', ['role' => $role->name, 'user_id' => $request->user()->id]);

        return redirect()->route('roles.index')->with('success', "Role {$role->label} was deleted.");
    }

    /** Permissions that may be ticked, grouped for display. */
    private function permissionGroups(Role $role)
    {
        return Permission::forRole($role)->orderBy('id')->get()->groupBy('group');
    }

    private function uniqueName(string $label): string
    {
        $base = Str::slug($label) ?: 'role';
        $name = $base;
        $i = 2;

        while (Role::where('name', $name)->exists()) {
            $name = $base . '-' . $i++;
        }

        return $name;
    }
}
