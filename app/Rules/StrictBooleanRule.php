<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrictBooleanRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Validates that the value is strictly a boolean (true or false).
     * Rejects integers (0, 1), strings ("0", "1", "true", "false"), and other types.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_bool($value)) {
            $fail("The {$attribute} field must be a boolean value (true or false).");
        }
    }
}
