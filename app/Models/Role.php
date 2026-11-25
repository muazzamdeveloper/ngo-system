<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** The Super Admin role always has everything, so its permissions can never be edited. */
    public const LOCKED = ['super-admin'];

    /** Roles that can never be given to a staff user from the Staff users screen. */
    public const NOT_ASSIGNABLE = ['super-admin', 'member'];

    protected $fillable = ['name', 'label', 'description', 'is_system'];

    /** The Member role holds only "Member portal" permissions and never admin access. */
    public function isMemberRole(): bool
    {
        return $this->name === 'member';
    }

    public function isLocked(): bool
    {
        return in_array($this->name, self::LOCKED, true);
    }

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
