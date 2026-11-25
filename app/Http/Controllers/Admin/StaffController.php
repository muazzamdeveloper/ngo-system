<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::staff()->with('role')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', [
            'staff' => new User(),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // forceFill: role_id and the flags are deliberately not mass-assignable.
        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $data['role_id'],
            'is_active' => true,
            'must_change_password' => true,
        ])->save();

        Log::info('Staff user created', ['target_user_id' => $user->id, 'role_id' => $user->role_id, 'user_id' => $request->user()->id]);

        return redirect()->route('users.index')->with('success', "Staff user {$user->name} was created with the {$user->role?->label} starting access. They must choose a new password at first login.");
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeTarget($request, $user);

        return view('admin.users.edit', [
            'staff' => $user,
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);

        $data = $request->validated();

        $roleChanged = (int) $user->role_id !== (int) $data['role_id'];

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->must_change_password = true;
        }

        $user->save();

        Log::info('Staff user updated', ['target_user_id' => $user->id, 'role_id' => $user->role_id, 'user_id' => $request->user()->id]);

        return redirect()->route('users.index')->with('success', "Staff user {$user->name} was updated.".($roleChanged ? ' Their access was reset to the new role preset; review it under User access.' : ''));
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);

        $user->forceFill(['is_active' => false])->save();

        Log::info('Staff user deactivated', ['target_user_id' => $user->id, 'user_id' => $request->user()->id]);

        return redirect()->route('users.index')->with('success', "{$user->name} can no longer log in.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);

        $user->forceFill(['is_active' => true])->save();

        Log::info('Staff user activated', ['target_user_id' => $user->id, 'user_id' => $request->user()->id]);

        return redirect()->route('users.index')->with('success', "{$user->name} can log in again.");
    }

    /**
     * Who may be touched from this screen: never yourself, never a super admin,
     * never a member's login, and never someone whose role is stronger than what you may hand out.
     */
    private function authorizeTarget(Request $request, User $target): void
    {
        $actor = $request->user();

        abort_if($target->id === $actor->id, 403, 'You cannot change your own account here. Use "Change password" instead.');
        abort_if($target->isSuperAdmin(), 403, 'The super admin account cannot be changed here.');
        abort_if($target->member()->exists() || $target->role?->name === 'member', 404);
        abort_if($target->role !== null && ! $actor->canAssignRole($target->role), 403, 'You cannot manage a user with this role.');
    }

    /** @return Collection<int, Role> */
    private function assignableRoles(User $actor): Collection
    {
        return Role::orderBy('label')->get()->filter(fn (Role $role) => $actor->canAssignRole($role))->values();
    }
}
