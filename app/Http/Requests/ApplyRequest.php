<?php

namespace App\Http\Requests;

use App\Models\CardSetting;
use App\Models\Member;
use App\Rules\AadhaarNumber;
use App\Services\MemberDocumentService;
use App\Support\IndianStates;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The public membership application. Anyone may send it, so every rule here is strict. */
class ApplyRequest extends FormRequest
{
    public const DUPLICATE_MESSAGE = 'We could not accept this application. If you have applied before, please contact our office.';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['first_name', 'last_name'] as $field) {
            $clean[$field] = preg_replace('/\s+/u', ' ', trim((string) $this->input($field)));
        }

        // "98765 43210" or "98765-43210" are fine; digits only are stored.
        foreach (['mobile', 'whatsapp'] as $field) {
            if ($this->filled($field)) {
                $clean[$field] = preg_replace('/[\s\-]+/', '', (string) $this->input($field));
            }
        }

        if ($this->filled('aadhaar')) {
            $clean['aadhaar'] = preg_replace('/[\s\-]+/', '', (string) $this->input('aadhaar'));
        }

        if ($this->filled('email')) {
            $clean['email'] = strtolower(trim((string) $this->input('email')));
        }

        $this->merge($clean);
    }

    public function rules(): array
    {
        $paymentRequired = CardSetting::current()->hasImage('payment_qr');

        $documents = MemberDocumentService::rules();
        $documents['aadhaar_front'] = $this->required($documents['aadhaar_front']);
        $documents['aadhaar_back'] = $this->required($documents['aadhaar_back']);

        if ($paymentRequired) {
            $documents['payment_screenshot'] = $this->required($documents['payment_screenshot']);
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(Member::GENDERS)],
            'father_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'blood_group' => ['required', Rule::in(Member::BLOOD_GROUPS)],
            'mobile' => ['required', 'regex:/^\+?[0-9\s-]{10,20}$/'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9\s-]{10,20}$/'],
            'email' => ['required', 'email', 'max:255'],
            'aadhaar' => ['required', 'string', 'max:20', new AadhaarNumber()],
            'state' => ['required', Rule::in(IndianStates::ALL)],
            'district' => ['required', 'string', 'max:100'],
            'work_area' => ['required', 'string', 'max:21'],
            'pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
            'address' => ['required', 'string', 'max:500'],
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ] + $documents;
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid mobile number (10 digits or more).',
            'whatsapp.regex' => 'Enter a valid WhatsApp number (10 digits or more).',
            'pincode.regex' => 'The pin code must be exactly 6 digits.',
            'work_area.max' => 'The work area must be 21 characters or fewer (it is printed on your ID card).',
                        'date_of_birth.before' => 'The date of birth must be in the past.',
            'photo.required' => 'Please upload your photo.',
            'photo.max' => 'The photo must not be larger than 2 MB.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP image.',
            'aadhaar_front.required' => 'Please upload the front of your Aadhaar card.',
            'aadhaar_back.required' => 'Please upload the back of your Aadhaar card.',
            'payment_screenshot.required' => 'Please upload a screenshot of your payment.',
        ] + MemberDocumentService::messages();
    }

    public function attributes(): array
    {
        return [
            'aadhaar' => 'Aadhaar number',
            'date_of_birth' => 'date of birth',
            'work_area' => 'work area',
            'father_name' => 'father name',
            'blood_group' => 'blood group',
        ];
    }

    /**
     * An applicant who already exists (same Aadhaar or same email, in any status) gets
     * one neutral message that does not say which detail matched, so the form cannot
     * be used to find out who is a member.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $exists = Member::findByAadhaar((string) $this->input('aadhaar')) !== null
                || Member::query()->where('email', $this->input('email'))->exists();

            if ($exists) {
                $validator->errors()->add('form', self::DUPLICATE_MESSAGE);
            }
        });
    }

    /** @param array<int, string> $rules */
    private function required(array $rules): array
    {
        return array_merge(['required'], array_values(array_diff($rules, ['nullable'])));
    }
}
