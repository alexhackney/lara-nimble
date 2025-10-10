<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Rules;

use AlexHackney\LaraNimble\Enums\StreamProtocol;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a value is a valid streaming protocol.
 */
class StreamProtocolRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $validProtocols = array_map(
            fn ($case) => $case->value,
            StreamProtocol::cases()
        );

        if (! in_array(strtolower($value), $validProtocols, true)) {
            $protocols = implode(', ', $validProtocols);
            $fail("The :attribute must be one of: {$protocols}.");
        }
    }
}
