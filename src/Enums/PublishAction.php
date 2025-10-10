<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Enums;

/**
 * Actions for publishing streams.
 */
enum PublishAction: string
{
    case PUBLISH = 'publish';
    case UNPUBLISH = 'unpublish';
}
