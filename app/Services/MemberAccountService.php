<?php

namespace App\Services;

use App\Exceptions\MemberAccountException;
use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use App\Notifications\MemberAccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MemberAccountService
{
    /**
     * Create a login for an approved member.
     *
     * @return array{user: User, password: string, generated: bool, emailed: bool}
     */
    public function create(Member $member, ?string $password, bool $sendEmail): array
    {
        $generated = $password === null || $password === '';
        $password = $generated ? Str::password(12, symbols: false) : $password;

        $user = DB::transaction(function () use ($member, $password) {
            $locked = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Member::STATUS_APPROVED) {
                throw new MemberAccountException('Only approved members can get a login account.');
            }

            if ($locked->user_id !== null) {
                throw new MemberAccountException('This member already has a login account.');
            }

            if (blank($locked->email)) {
                throw new MemberAccountException('Add an email address to this member first (use Edit).');
            }

            if (User::where('email', $locked->email)->exists()) {
                throw new MemberAccountException('A login with this email address already exists.');
            }

            $roleId = Role::where('name', 'member')->value('id');

            if ($roleId === null) {
                throw new MemberAccountException('The Member role is missing. Run "php artisan db:seed" once.');
            }

            $user = new User();
            $user->forceFill([
                'name' => $locked->full_name,
                'email' => $locked->email,
                'password' => $password,
                'is_active' => true,
                'must_change_password' => true,
                'role_id' => $roleId,
            ])->save();

            $locked->user_id = $user->id;
            $locked->save();

            return $user;
        });

        $member->refresh();

        $emailed = false;

        if ($sendEmail) {
            try {
                $user->notify(new MemberAccountCreated($password));
                $emailed = true;
            } catch (Throwable $e) {
                // The account exists; only the email failed. Never log the password.
                Log::warning('Member account email could not be sent', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return ['user' => $user, 'password' => $password, 'generated' => $generated, 'emailed' => $emailed];
    }
}
