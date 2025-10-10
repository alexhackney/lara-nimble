<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Exceptions;

use Throwable;

/**
 * Exception thrown when Nimble API returns an error response.
 */
class NimbleApiException extends NimbleException
{
    public function __construct(
        string $message = "",
        int $code = 0,
        public readonly ?array $responseData = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
