<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends StoreMemberRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('members.edit') ?? false;
    }

    public function rules(): array
    {
        /** @var Member $member */
        $member = $this->route('member');

        $rules = parent::rules();

        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('members', 'email')->ignore($member->id)];

        // Leave blank to keep the current number. A new number must not belong to someone else.
        $rules['aadhaar'][count($rules['aadhaar']) - 1] = function (string $attribute, mixed $value, \Closure $fail) use ($member) {
            $other = Member::findByAadhaar((string) $value);

            if ($other && $other->id !== $member->id) {
                $fail('A member with this Aadhaar number already exists.');
            }
        };

        $rules['remove_photo'] = ['nullable', 'boolean'];

        foreach (array_keys(Member::DOCUMENT_COLUMNS) as $field) {
            $rules['remove_' . $field] = ['nullable', 'boolean'];
        }

        return $rules;
    }
}
