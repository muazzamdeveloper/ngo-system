<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Per-user module access. Super admin only (route middleware).
 * Each staff account gets its own ticked permissions, so two managers on the same role
 * can have different modules. Roles are only presets that can be copied onto an account.
 */
class UserAccessController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::staff()
            ->with('role')
            ->withCount('directPermissions')
            ->where('is_super_admin', false)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.access.index', ['users' => $users, 'q' => $q]);
    }

    public function edit(User $user): View
    {
        $this->authorizeStaff($user);

        return view('admin.access.edit', [
            'staff' => $user->load('role'),
            'groups' => PermissionCatalog::groups(),
            'granted' => $user->directPermissions()->pluck('permissions.id')->all(),
            'presets' => $this->presetRoles(),
        ]);
    }

    public function update(Request $request, User $user, RolePermissionService $service): RedirectResponse
    {
        $this->authorizeStaff($user);

        $selected = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['integer']])['permissions'] ?? [];

        $ids = array_values(array_intersect(array_map('intval', $selected), PermissionCatalog::allowedIds()));
        $saved = $service->idsFor($ids);

        $user->directPermissions()->sync($saved);

        Log::info('Staff access changed', ['target_user_id' => $user->id, 'count' => count($saved), 'user_id' => $request->user()->id]);

        return redirect()->route('access.edit', $user)->with('success', "Access for {$user->name} saved. Changes apply on their next page load.");
    }

    public function preset(Request $request, User $user): RedirectResponse
    {
        $this->authorizeStaff($user);

        $data = $request->validate(['role_id' => ['required', 'integer', 'exists:roles,id']]);
        $role = Role::query()->findOrFail($data['role_id']);

        abort_if(in_array($role->name, Role::NOT_ASSIGNABLE, true), 422, 'That preset cannot be used for staff accounts.');

        $user->applyRolePreset($role);

        Log::info('Staff access preset applied', ['target_user_id' => $user->id, 'role_id' => $role->id, 'user_id' => $request->user()->id]);

        return redirect()->route('access.edit', $user)->with('success', "The {$role->label} preset was copied to {$user->name}. Review the ticks and save if you want to change anything.");
    }

    /** Only staff accounts are managed here: never the super admin, never a member's login. */
    private function authorizeStaff(User $user): void
    {
        abort_if($user->isSuperAdmin() || $user->member()->exists() || $user->role?->name === 'member', 404);
    }

    private function presetRoles()
    {
        return Role::query()->whereNotIn('name', Role::NOT_ASSIGNABLE)->orderBy('label')->get();
    }
}
