<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Creates the permissions for the admin modules (config/admin.php). Safe to run many times.
 * A permission that is new to the database is given to the Coordinator role only when its
 * default says so; existing choices made by the super admin are never changed.
 */
class AdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $coordinator = Role::query()->where('name', 'coordinator')->first();

        foreach (config('admin.permissions', []) as $name => [$label, $group, $coordinatorDefault]) {
            $permission = Permission::query()->updateOrCreate(
                ['name' => $name],
                ['label' => $label, 'group' => $group],
            );

            if ($permission->wasRecentlyCreated && $coordinatorDefault && $coordinator) {
                $coordinator->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
    }
}
