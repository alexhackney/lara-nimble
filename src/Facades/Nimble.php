<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Facades;

use AlexHackney\LaraNimble\Testing\FakeNimbleClient;
use AlexHackney\LaraNimble\Testing\NimbleFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \AlexHackney\LaraNimble\Client\NimbleClient client()
 * @method static string getBaseUrl()
 * @method static \AlexHackney\LaraNimble\Services\StreamService streams()
 * @method static \AlexHackney\LaraNimble\Services\SessionService sessions()
 * @method static \AlexHackney\LaraNimble\Services\DvrService dvr()
 * @method static \AlexHackney\LaraNimble\Services\RestreamService restream()
 * @method static \AlexHackney\LaraNimble\Services\ServerService server()
 * @method static \AlexHackney\LaraNimble\Services\CacheService cache()
 * @method static \AlexHackney\LaraNimble\Services\PublishControlService publishControl()
 * @method static \AlexHackney\LaraNimble\Services\ProtocolService protocols()
 * @method static \AlexHackney\LaraNimble\Services\IcecastService icecast()
 * @method static \AlexHackney\LaraNimble\Services\Scte35Service scte35()
 *
 * @see \AlexHackney\LaraNimble\Nimble
 */
class Nimble extends Facade
{
    /**
     * Replace the bound Nimble manager with a fake for testing.
     *
     * Stubs map endpoint patterns (optionally "METHOD /path", wildcards
     * allowed) to response arrays or closures. Unstubbed requests get
     * {"status": "Ok"}. Real services and DTO parsing still run.
     *
     * @param  array<string, array|\Closure>  $stubs
     */
    public static function fake(array $stubs = []): NimbleFake
    {
        $fake = new NimbleFake(new FakeNimbleClient($stubs));

        static::swap($fake);

        return $fake;
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \AlexHackney\LaraNimble\Nimble::class;
    }
}
