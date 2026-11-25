<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use App\Rules\AadhaarNumber;
use App\Services\MemberDocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('members.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Allow "98765 43210" or "98765-43210" and store digits only.
        foreach (['mobile', 'whatsapp'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => preg_replace('/[\s\-]+/', '', (string) $this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'father_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::in(Member::GENDERS)],
            'blood_group' => ['nullable', Rule::in(Member::BLOOD_GROUPS)],
            'mobile' => ['required', 'regex:/^\+?[0-9]{10,15}$/'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'email' => ['nullable', 'email', 'max:255', 'unique:members,email'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'work_area' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'aadhaar' => [
                'nullable',
                'string',
                'regex:/^[0-9\s\-]+$/',
                new AadhaarNumber(),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Member::findByAadhaar((string) $value)) {
                        $fail('A member with this Aadhaar number already exists.');
                    }
                },
            ],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ] + MemberDocumentService::rules();
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid mobile number (10 to 15 digits).',
            'pincode.regex' => 'The pincode must be exactly 6 digits.',
            'aadhaar.regex' => 'The Aadhaar number may contain only digits, spaces or dashes.',
            'photo.max' => 'The photo must not be larger than 2 MB.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP image.',
            'whatsapp.regex' => 'Enter a valid WhatsApp number (10 to 15 digits).',
        ] + MemberDocumentService::messages();
    }
}
