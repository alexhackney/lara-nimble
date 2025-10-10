<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AlexHackney\LaraNimble\Client\NimbleClient client()
 * @method static string getBaseUrl()
 * @method static \AlexHackney\LaraNimble\Services\StreamService streams()
 * @method static \AlexHackney\LaraNimble\Services\SessionService sessions()
 * @method static \AlexHackney\LaraNimble\Services\DvrService dvr()
 * @method static \AlexHackney\LaraNimble\Services\RestreamService restream()
 * @method static \AlexHackney\LaraNimble\Services\PullService pull()
 * @method static \AlexHackney\LaraNimble\Services\ServerService server()
 * @method static \AlexHackney\LaraNimble\Services\CacheService cache()
 *
 * @see \AlexHackney\LaraNimble\Nimble
 */
class Nimble extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \AlexHackney\LaraNimble\Nimble::class;
    }
}
