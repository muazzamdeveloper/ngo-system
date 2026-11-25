<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * What a member may change about themselves. Name, father's name, date of birth,
 * email and Aadhaar are deliberately missing: only staff can change those.
 */
class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('mobile')) {
            $this->merge(['mobile' => preg_replace('/[\s\-]+/', '', (string) $this->input('mobile'))]);
        }
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid mobile number (10 to 15 digits).',
            'pincode.regex' => 'The pincode must be exactly 6 digits.',
            'photo.max' => 'The photo must not be larger than 2 MB.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP image.',
        ];
    }
}
