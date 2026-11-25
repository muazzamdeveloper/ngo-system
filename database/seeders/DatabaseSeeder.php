<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * No demo or test users are created here on purpose.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(AdminPermissionsSeeder::class);
        $this->call(PolicyDefaultsSeeder::class);
    }
}
