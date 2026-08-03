<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Rules;

use AlexHackney\LaraNimble\Facades\Nimble;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a stream is currently live in Nimble.
 *
 * With an application passed to the constructor, the attribute value is
 * the stream name. Without one, the value must use "app/stream" form.
 */
class StreamExistsRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $app = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a string.');

            return;
        }

        if ($this->app !== null) {
            [$app, $stream] = [$this->app, $value];
        } else {
            $parts = explode('/', $value, 2);

            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                $fail('The :attribute must use the app/stream format.');

                return;
            }

            [$app, $stream] = $parts;
        }

        try {
            if (! Nimble::streams()->exists($app, $stream)) {
                $fail('The selected :attribute is not a live stream.');
            }
        } catch (\Exception) {
            $fail('The selected :attribute could not be verified.');
        }
    }
}
