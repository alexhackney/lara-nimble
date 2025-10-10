<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Events\CacheCleared;

/**
 * Service for managing Nimble server cache.
 */
class CacheService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Clear server cache.
     *
     * @param  string|null  $type  Cache type to clear (all, hls, dash)
     */
    public function clear(?string $type = null): bool
    {
        $params = [];
        if ($type !== null) {
            $params['type'] = $type;
        }

        $response = $this->client->post('/manage/cache/clear', $params);

        $success = $response->get('success', false) === true;

        if ($success) {
            try {
                event(new CacheCleared($type));
            } catch (\Throwable $e) {
                // Event dispatcher not available (e.g., in unit tests)
            }
        }

        return $success;
    }

    /**
     * Get cache statistics.
     */
    public function statistics(): array
    {
        $response = $this->client->get('/manage/cache/stats');

        return $response->data();
    }

    /**
     * Configure cache settings.
     */
    public function configure(array $settings): bool
    {
        $response = $this->client->post('/manage/cache/configure', $settings);

        return $response->get('success', false) === true;
    }
}
