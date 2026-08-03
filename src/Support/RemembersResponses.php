<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Optional short-TTL response caching for frequently polled endpoints.
 *
 * Controlled by the nimble.cache config block. Outside a Laravel
 * container (plain unit tests) or with caching disabled, the fetch
 * closure runs directly.
 */
trait RemembersResponses
{
    /**
     * @param  Closure(): array  $fetch
     */
    private function remember(string $key, Closure $fetch): array
    {
        $repository = $this->cacheRepository();

        if ($repository === null) {
            return $fetch();
        }

        $ttl = max(1, (int) config('nimble.cache.ttl', 2));
        $key = 'nimble:'.md5($this->client->getBaseUrl()).':'.$key;

        return $repository->remember($key, $ttl, $fetch);
    }

    private function cacheRepository(): ?Repository
    {
        try {
            if (! function_exists('app') || ! app()->bound('config')) {
                return null;
            }

            if (! (bool) config('nimble.cache.enabled', false)) {
                return null;
            }

            /** @var string|null $store */
            $store = config('nimble.cache.store');

            return cache()->store($store);
        } catch (\Throwable) {
            return null;
        }
    }
}
