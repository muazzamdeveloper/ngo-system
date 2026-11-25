<?php

namespace App\Rules;

use App\Models\Member;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AadhaarNumber implements ValidationRule
{
    private const D = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6], [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8], [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2], [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4], [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    private const P = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9], [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2], [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0], [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5], [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = Member::normalizeAadhaar((string) $value);

        if ($digits === null || ! preg_match('/^[0-9]{12}$/', $digits)) {
            $fail('Enter the 12-digit Aadhaar number. Spaces or dashes are fine.');

            return;
        }

        if (config('ngo.aadhaar_checksum') && ! $this->passesVerhoeff($digits)) {
            $fail('This Aadhaar number is not valid. Please check the digits.');
        }
    }

    private function passesVerhoeff(string $digits): bool
    {
        $c = 0;

        foreach (array_reverse(str_split($digits)) as $i => $digit) {
            $c = self::D[$c][self::P[$i % 8][(int) $digit]];
        }

        return $c === 0;
    }
}
