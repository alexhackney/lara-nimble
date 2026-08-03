<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\Events\CacheCleared;

/**
 * Service for Nimble's data cache (/manage/data_cache).
 *
 * The data cache holds responses for remote VOD and similar content,
 * addressed by cache keys derived from origin URLs.
 */
class CacheService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Resolve the cache key for an origin URL, or null when Nimble
     * does not return one.
     */
    public function key(string $url): ?string
    {
        $response = $this->client->post('/manage/data_cache/get_key', ['url' => $url]);

        $key = $response->get('key');

        return is_string($key) ? $key : null;
    }

    /**
     * Delete cached items by key and return the removed item list.
     *
     * A missing key yields an empty list. With dryRun, Nimble reports
     * what would be removed without removing it.
     *
     * @return array<int, string>
     */
    public function delete(string $key, bool $dryRun = false): array
    {
        $response = $this->client->post('/manage/data_cache/delete', [
            'key' => $key,
            'dry_run' => $dryRun,
        ]);

        /** @var array<int, string> $removed */
        $removed = $response->get('removed_items', []);

        if ($removed !== [] && ! $dryRun) {
            try {
                event(new CacheCleared($key));
            } catch (\Throwable) {
                // Event dispatcher not available (e.g., in unit tests)
            }
        }

        return $removed;
    }
}
