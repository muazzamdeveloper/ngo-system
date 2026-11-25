<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Safe to run many times: permissions are created if missing, and a role's
     * default permissions are only applied when the role is created, so changes
     * the super admin makes later are never overwritten.
     */
    public function run(): void
    {
        $permissions = [
            ['admin.access',          'Enter the admin panel',            'General'],
            ['members.view',          'View members',                     'Members'],
            ['members.create',        'Add members',                      'Members'],
            ['members.edit',          'Edit members',                     'Members'],
            ['members.deactivate',    'Deactivate, block or restore members', 'Members'],
            ['members.approve',       'Approve or reject members',        'Members'],
            ['members.view_aadhaar',  'See full Aadhaar numbers',         'Members'],
            ['members.create_account', 'Create login accounts for members', 'Members'],
            ['website.home',          'Edit the home page (slider, sections, pictures)', 'Website'],
            ['website.pages',         'Edit website pages (About us, Terms ...)', 'Website'],
            ['website.menus',         'Edit the menus, footer links and news ticker', 'Website'],
            ['posts.write',           'Write and edit their own posts (and publish them)', 'Posts'],
            ['posts.manage_all',      'Edit and delete anyone\'s posts, and manage categories', 'Posts'],
            ['portal.edit_profile',   'Edit their own contact details',   'Member portal'],
            ['portal.download_documents', 'Download their own ID card and joining letter', 'Member portal'],
            ['users.manage',          'Create and manage login accounts', 'Users'],
            ['roles.manage',          'Manage roles and permissions',     'Roles'],
            ['settings.manage',       'Manage organization settings',     'Settings'],
            ['card_settings.manage',  'Manage ID card, payment QR and letter settings', 'Settings'],
            ['payments.view',         'View application payments and screenshots', 'Payments'],
            ['payments.verify',       'Verify or reject application payments', 'Payments'],
            ['payment_settings.manage', 'Manage donation QR and payment settings', 'Payments'],
            ['audit.view',            'View the audit log',               'Security'],
        ];

        // Permissions that did not exist before this run. Existing roles receive
        // them only if the role's defaults include them (this is how new versions
        // hand out new permissions without undoing the super admin's own changes).
        $newPermissions = [];

        foreach ($permissions as [$name, $label, $group]) {
            $permission = Permission::updateOrCreate(['name' => $name], ['label' => $label, 'group' => $group]);

            if ($permission->wasRecentlyCreated) {
                $newPermissions[] = $name;
            }
        }

        $roles = [
            [
                'name' => 'super-admin',
                'label' => 'Super Admin',
                'description' => 'Full control of the whole system. Always has every permission.',
                'permissions' => [],
            ],
            [
                'name' => 'manager',
                'label' => 'Manager',
                'description' => 'Manages and approves members.',
                'permissions' => ['admin.access', 'members.view', 'members.create', 'members.edit', 'members.approve', 'members.create_account'],
            ],
            [
                'name' => 'coordinator',
                'label' => 'Coordinator',
                'description' => 'Can only view members.',
                'permissions' => ['admin.access', 'members.view'],
            ],
            [
                'name' => 'member',
                'label' => 'Member',
                'description' => 'Approved member. No admin panel access; own profile only.',
                'permissions' => ['portal.edit_profile', 'portal.download_documents'],
            ],
        ];

        foreach ($roles as $data) {
            $role = Role::firstOrCreate(
                ['name' => $data['name']],
                ['label' => $data['label'], 'description' => $data['description'], 'is_system' => true]
            );

            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(
                    Permission::whereIn('name', $data['permissions'])->pluck('id')
                );
            } elseif ($newPermissions !== []) {
                $role->permissions()->syncWithoutDetaching(
                    Permission::whereIn('name', array_intersect($data['permissions'], $newPermissions))->pluck('id')
                );
            }
        }

        // Link an existing super admin account to the Super Admin role.
        $superRoleId = Role::where('name', 'super-admin')->value('id');
        User::where('is_super_admin', true)->whereNull('role_id')->update(['role_id' => $superRoleId]);
    }
}
