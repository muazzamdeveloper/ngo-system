<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        return parent::authorize() && ! $role->isLocked();
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        $rules = parent::rules();
        $rules['label'] = ['required', 'string', 'max:60', Rule::unique('roles', 'label')->ignore($role->id)];

        return $rules;
    }
}
