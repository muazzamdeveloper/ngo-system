<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A link on this site ("/apply") or a web address ("https://..."). Nothing else, so no "javascript:" tricks. */
class SiteUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = (string) $value;

        $local = str_starts_with($url, '/') && ! str_starts_with($url, '//');
        $web = (bool) preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $url);

        if (! ($local || $web) || preg_match('/[\s\x00-\x1f]/', $url) || strlen($url) > 255) {
            $fail('Use a link like /apply or https://example.com (no spaces).');
        }
    }
}
