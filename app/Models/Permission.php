<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    /**
     * Permissions that only the Super Admin has, and that can never be given to a role.
     * (Managing roles and reading the audit log.)
     */
    public const SUPER_ADMIN_ONLY = ['roles.manage', 'audit.view'];

    /** Permissions that control what a member can do in their own area. Only the Member role may hold them. */
    public const PORTAL_PREFIX = 'portal.';

    protected $fillable = ['name', 'label', 'group'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /** The permissions that may be ticked for this particular role. */
    public function scopeForRole(Builder $query, Role $role): Builder
    {
        $query->assignable();

        return $role->isMemberRole()
            ? $query->where('name', 'like', self::PORTAL_PREFIX . '%')
            : $query->where('name', 'not like', self::PORTAL_PREFIX . '%');
    }

    /** Permissions the super admin may tick for a role. */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query->whereNotIn('name', self::SUPER_ADMIN_ONLY);
    }
}
