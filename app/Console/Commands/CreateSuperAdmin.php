<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin';

    protected $description = 'Create the super admin account securely (only if none exists)';

    public function handle(): int
    {
        if (User::where('is_super_admin', true)->exists()) {
            $this->error('A super admin already exists. No new account was created.');

            return self::FAILURE;
        }

        $name = $this->ask('Full name');
        $email = $this->ask('Email address');
        $password = $this->secret('Password (minimum 10 characters, typing is hidden)');
        $confirmation = $this->secret('Confirm password');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:10', 'confirmed'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        // forceFill is used because is_super_admin is intentionally not mass-assignable.
        // The password is hashed automatically by the model's "hashed" cast.
        $user = new User();
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_super_admin' => true,
            'is_active' => true,
            'role_id' => Role::where('name', 'super-admin')->value('id'),
        ])->save();

        $this->info("Super admin created: {$user->email}");

        return self::SUCCESS;
    }
}