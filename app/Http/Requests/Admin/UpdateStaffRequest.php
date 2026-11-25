<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStaffRequest extends StoreStaffRequest
{
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        $rules = parent::rules();
        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)];
        $rules['password'] = ['nullable', 'confirmed', Password::min(10)->letters()->numbers()];

        return $rules;
    }
}
