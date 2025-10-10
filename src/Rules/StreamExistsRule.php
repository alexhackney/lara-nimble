<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Rules;

use Closure;
use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a stream exists in Nimble.
 */
class StreamExistsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        try {
            Nimble::streams()->get($value);
        } catch (\Exception $e) {
            $fail('The selected :attribute does not exist.');
        }
    }
}
