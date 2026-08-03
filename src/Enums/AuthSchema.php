<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Enums;

/**
 * Authentication schemas supported by Nimble RTMP republishing.
 */
enum AuthSchema: string
{
    case NONE = 'NONE';
    case NIMBLE = 'NIMBLE';
    case AKAMAI = 'AKAMAI';
    case LIMELIGHT = 'LIMELIGHT';
    case PERISCOPE = 'PERISCOPE';
}
