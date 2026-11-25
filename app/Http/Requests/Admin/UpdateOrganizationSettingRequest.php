<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'membership_prefix' => strtoupper(trim((string) $this->input('membership_prefix'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'membership_prefix' => ['required', 'string', 'regex:/^[A-Z0-9]{1,5}$/'],
            'phone' => ['nullable', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:300'],
            'address_secondary' => ['nullable', 'string', 'max:300'],
            'working_hours' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'regex:/^\+?[0-9\s\-]{10,20}$/'],
            'facebook_url' => ['nullable', 'url:http,https', 'max:255'],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255'],
            'youtube_url' => ['nullable', 'url:http,https', 'max:255'],
            'ticker_label' => ['nullable', 'string', 'max:30'],
            'site_title' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/', $this->readableColor()],
            'reset_color' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'mimetypes:image/png,image/jpeg,image/webp,image/x-icon,image/vnd.microsoft.icon', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ];
    }

    /** Buttons carry white text, so a very light colour would be unreadable. */
    private function readableColor(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! is_string($value) || ! preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                return;
            }

            [$r, $g, $b] = array_map(function (int $channel) {
                $c = $channel / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, sscanf($value, '#%02x%02x%02x'));

            $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

            // Contrast of white text on this colour must be at least 3:1.
            if ((1.05) / ($luminance + 0.05) < 3) {
                $fail('This colour is too light for white buttons and text. Please pick a darker colour.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'logo.max' => 'The logo must not be larger than 2 MB.',
            'logo.mimes' => 'The logo must be a PNG, JPG or WebP image.',
            'logo.mimetypes' => 'The logo must be a PNG, JPG or WebP image.',
            'favicon.max' => 'The site icon must not be larger than 512 KB.',
            'favicon.mimes' => 'The site icon must be a PNG, JPG, WebP or ICO file.',
            'favicon.mimetypes' => 'The site icon must be a PNG, JPG, WebP or ICO file.',
            'primary_color.regex' => 'Use a colour like #1f6f5c.',
            'phone.regex' => 'Enter a phone number like +91 98765 43210.',
            'whatsapp.regex' => 'Enter the WhatsApp number with country code, like +91 98765 43210.',
            'membership_prefix.regex' => 'The prefix must be 1 to 5 letters or digits, with no spaces or symbols.',
        ];
    }
}
