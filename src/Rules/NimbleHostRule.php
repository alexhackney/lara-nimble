<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Rules;

use Closure;
use AlexHackney\LaraNimble\Facades\Nimble;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a Nimble server host is reachable.
 */
class NimbleHostRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a valid hostname or IP address.');
            return;
        }

        try {
            // Attempt to connect to the Nimble server
            Nimble::server()->status();
        } catch (\Exception $e) {
            $fail('The :attribute is not a reachable Nimble server.');
        }
    }
}
