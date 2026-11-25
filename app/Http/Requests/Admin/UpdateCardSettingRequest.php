<?php

namespace App\Http\Requests\Admin;

use App\Models\CardSetting;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCardSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('card_settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $terms = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', (string) $this->input('card_terms')) ?: []),
            fn (string $line) => $line !== ''
        ));

        $this->merge([
            'card_terms' => implode("\n", $terms),
            'letter_template' => CardSetting::cleanTemplate((string) $this->input('letter_template')),
        ]);
    }

    public function rules(): array
    {
        $image = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=4000,max_height=4000'];

        $rules = [
            'fee_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            'fee_currency' => ['required', 'string', 'max:10'],

            'card_heading' => ['required', 'string', 'max:30'],
            'card_designation' => ['required', 'string', 'max:60'],
            'card_validity_months' => ['required', 'integer', 'min:1', 'max:120'],
            'card_aadhaar_display' => ['required', Rule::in(array_keys(CardSetting::AADHAAR_DISPLAY))],
            'application_notice' => ['nullable', 'string', 'max:500'],
            'card_terms' => [
                'required', 'string', 'max:3000',
                function (string $attribute, mixed $value, Closure $fail) {
                    $lines = preg_split('/\R/u', (string) $value) ?: [];

                    if (count($lines) > 10) {
                        $fail('Use at most 10 terms.');
                    }

                    foreach ($lines as $line) {
                        if (mb_strlen($line) > 300) {
                            $fail('Each term must be 300 characters or fewer.');
                            break;
                        }
                    }
                },
            ],
            'udyam_number' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 \-]+$/'],
            'website_url' => ['nullable', 'string', 'max:255', 'url:http,https'],

            'letter_site_title' => ['nullable', 'string', 'max:100'],
            'letter_signatory_name' => ['nullable', 'string', 'max:100'],
            'letter_signatory_role' => ['required', 'string', 'max:60'],
            'letter_address' => ['nullable', 'string', 'max:300'],
            'letter_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\- ]+$/'],
            'letter_email' => ['nullable', 'email', 'max:255'],
            'letter_date' => ['nullable', 'date'],
            'letter_joining_date' => ['nullable', 'date'],
            'letter_template' => [
                'required', 'string', 'max:20000',
                function (string $attribute, mixed $value, Closure $fail) {
                    preg_match_all('/\{([A-Za-z_]+)\}/', (string) $value, $found);
                    $unknown = array_values(array_unique(array_diff($found[1], array_keys(CardSetting::PLACEHOLDERS))));

                    if ($unknown !== []) {
                        $fail('Unknown placeholder: {' . implode('}, {', $unknown) . '}. Check the spelling against the list below.');
                    }
                },
            ],
        ];

        foreach (array_keys(CardSetting::IMAGES) as $key) {
            $rules[$key] = $image;
            $rules['remove_' . $key] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'card_validity_months.min' => 'Validity must be at least 1 month.',
            'card_validity_months.max' => 'Validity can be at most 120 months.',
            'udyam_number.regex' => 'The Udyam number may contain only letters, digits, spaces and hyphens.',
            'letter_phone.regex' => 'The phone number may contain only digits, spaces and + ( ) -.',
            'website_url.url' => 'Enter the full website address, starting with http:// or https://.',
            'fee_amount.decimal' => 'The fee can have at most 2 decimal places.',
        ];

        foreach (CardSetting::IMAGE_LABELS as $key => $label) {
            $messages[$key . '.image'] = "The {$label} must be a JPG or PNG image.";
            $messages[$key . '.mimes'] = "The {$label} must be a JPG or PNG image.";
            $messages[$key . '.max'] = "The {$label} must not be larger than 2 MB.";
            $messages[$key . '.dimensions'] = "The {$label} must not be larger than 4000 x 4000 pixels.";
        }

        return $messages;
    }
}
