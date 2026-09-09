<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidAadhar implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Basic format check (12 digits)
        if (!preg_match('/^[0-9]{12}$/', $value)) {
            return false;
        }

        // Verhoeff algorithm check digit validation
        return $this->verhoeffCheck($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not a valid Aadhar number.';
    }

    /**
     * Verhoeff algorithm implementation for Aadhar validation
     *
     * @param string $num
     * @return bool
     */
    private function verhoeffCheck($num)
    {
        // Multiplication table
        $d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];

        // Permutation table
        $p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];

        $checksum = 0;
        $digits = str_split(strrev($num));

        foreach ($digits as $i => $digit) {
            $checksum = $d[$checksum][$p[($i % 8)][(int)$digit]];
        }

        return $checksum === 0;
    }
}
