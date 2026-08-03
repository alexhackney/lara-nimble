<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\Support\RemembersResponses;
use Illuminate\Support\Collection;

/**
 * Service for inspecting live streams via GET /manage/live_streams_status.
 *
 * Nimble's native API exposes currently-running streams only; a stream
 * that is not being published simply does not appear.
 */
class StreamService
{
    use RemembersResponses;

    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * List all currently live streams across all applications.
     *
     * Served from a short-TTL cache when nimble.cache.enabled is on.
     *
     * @return Collection<int, StreamDto>
     */
    public function list(): Collection
    {
        /** @var array<int, array<string, mixed>> $groups */
        $groups = $this->remember('live_streams_status', function (): array {
            return $this->client->get('/manage/live_streams_status')->data();
        });

        return collect($groups)->flatMap(function (array $group) {
            $app = $group['app'] ?? '';

            /** @var array<int, array<string, mixed>> $streams */
            $streams = $group['streams'] ?? [];

            return collect($streams)->map(function (array $stream) use ($app) {
                return StreamDto::fromArray(['app' => $app] + $stream);
            });
        })->values();
    }

    /**
     * List currently live streams for one application.
     *
     * @return Collection<int, StreamDto>
     */
    public function byApp(string $app): Collection
    {
        return $this->list()
            ->filter(fn (StreamDto $stream) => $stream->app === $app)
            ->values();
    }

    /**
     * Find a live stream, or null when it is not currently publishing.
     */
    public function find(string $app, string $stream): ?StreamDto
    {
        return $this->list()->first(
            fn (StreamDto $dto) => $dto->app === $app && $dto->stream === $stream
        );
    }

    /**
     * Check whether a stream is currently live.
     */
    public function exists(string $app, string $stream): bool
    {
        return $this->find($app, $stream) !== null;
    }
}
