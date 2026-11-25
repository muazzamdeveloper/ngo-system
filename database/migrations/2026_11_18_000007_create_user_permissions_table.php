<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user module access. Each staff account now holds its own permission list, chosen by the
 * super admin, so two managers on the same role can have different modules.
 * Existing staff keep exactly the access their role gave them: that is copied across here.
 * Member portal accounts keep using their role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        $staff = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('roles.name', '!=', 'member')
            ->where('roles.name', '!=', 'super-admin')
            ->where('users.is_super_admin', false)
            ->select('users.id', 'users.role_id')
            ->get();

        foreach ($staff as $user) {
            $permissionIds = DB::table('permission_role')->where('role_id', $user->role_id)->pluck('permission_id');

            foreach ($permissionIds as $permissionId) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
