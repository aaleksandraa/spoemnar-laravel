<?php

namespace App\Support;

class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /**
     * Require uppercase, lowercase, digit and at least one special character.
     * Allow a broad set of special characters and disallow whitespace.
     */
    public const COMPLEXITY_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d])[^\s]+$/';

    /**
     * @return array<int, string>
     */
    public static function validationRules(): array
    {
        return [
            'required',
            'string',
            'min:'.self::MIN_LENGTH,
            'regex:'.self::COMPLEXITY_REGEX,
        ];
    }
}
