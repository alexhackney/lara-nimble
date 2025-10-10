<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Enums;

/**
 * Stream status states.
 */
enum StreamStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';
}
