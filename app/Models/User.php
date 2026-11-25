<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Mass-assignable fields. is_super_admin, is_active, role_id and must_change_password are
     * deliberately NOT listed, so they can never be set through a submitted form.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<int, string>|null Permission names loaded once per request. */
    protected ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // A new staff account, or one whose role changed, starts with a copy of that role's permissions.
        // Member accounts use their role directly and are skipped. After that the grants are per account.
        static::saved(function (User $user) {
            if (! ($user->wasRecentlyCreated || $user->wasChanged('role_id'))) {
                return;
            }

            $user->unsetRelation('role');

            if ($user->is_super_admin || $user->role === null || $user->role->isMemberRole()) {
                return;
            }

            $user->applyRolePreset($user->role);
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /** Staff accounts: everyone who is not a member's login. */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereDoesntHave('member')
            ->where(function (Builder $query) {
                $query->whereNull('role_id')
                    ->orWhereHas('role', fn (Builder $role) => $role->where('name', '!=', 'member'));
            });
    }

    /** Permissions granted to this staff account one by one by the super admin. */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')->withTimestamps();
    }

    /**
     * Names of the permissions this user holds (loaded once per request).
     * Member portal accounts use their role. Staff use only their own grants, so two
     * staff on the same role can still have different access.
     */
    public function permissionNames(): array
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->role?->isMemberRole()
                ? $this->role->permissions()->pluck('name')->all()
                : $this->directPermissions()->pluck('name')->all();
        }

        return $this->permissionCache;
    }

    /** Replace this account's grants with a copy of a role's permissions (the role is only a starting preset). */
    public function applyRolePreset(Role $role): void
    {
        if ($role->isMemberRole()) {
            return;
        }

        $ids = app(\App\Services\RolePermissionService::class)
            ->idsFor($role->permissions()->pluck('permissions.id')->all());

        $this->directPermissions()->sync($ids);
        $this->permissionCache = null;
    }

    /**
     * True when the user may perform the given permission.
     * The super admin always may; everyone else needs it through their role.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionNames(), true);
    }

    /**
     * May this user give the role to someone else? Never for the locked roles
     * (Super Admin, Member). The super admin may give any other role; everyone
     * else only roles whose permissions they already hold themselves, so nobody
     * can hand out more power than they have.
     */
    public function canAssignRole(Role $role): bool
    {
        if (in_array($role->name, Role::NOT_ASSIGNABLE, true)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return array_diff($role->permissions()->pluck('name')->all(), $this->permissionNames()) === [];
    }
}
