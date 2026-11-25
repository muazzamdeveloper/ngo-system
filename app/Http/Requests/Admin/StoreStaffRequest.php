<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'integer', $this->assignableRole()],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }

    /** The chosen role must exist and be one this person is allowed to hand out. */
    protected function assignableRole(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $role = Role::find($value);

            if (! $role || ! $this->user()->canAssignRole($role)) {
                $fail('You cannot give this role.');
            }
        };
    }
}
