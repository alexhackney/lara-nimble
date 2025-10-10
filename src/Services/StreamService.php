<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\StreamDto;
use AlexHackney\LaraNimble\DTOs\StreamStatsDto;
use AlexHackney\LaraNimble\Events\StreamPublished;
use AlexHackney\LaraNimble\Events\StreamUnpublished;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble streams.
 */
class StreamService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {
    }

    /**
     * List all streams.
     *
     * @return Collection<int, StreamDto>
     */
    public function list(): Collection
    {
        $response = $this->client->get('/manage/streams');

        $streams = $response->get('streams', []);

        return collect($streams)->map(function (array $streamData) {
            return StreamDto::fromArray($streamData);
        });
    }

    /**
     * Get details of a specific stream.
     */
    public function get(string $streamId): StreamDto
    {
        $response = $this->client->get("/manage/stream/{$streamId}");

        return StreamDto::fromArray($response->data());
    }

    /**
     * Publish a stream.
     */
    public function publish(string $app, string $stream): bool
    {
        $response = $this->client->post('/manage/publish', [
            'app' => $app,
            'stream' => $stream,
            'action' => 'publish',
        ]);

        $success = $response->get('success', false) === true;

        if ($success) {
            try {
                event(new StreamPublished($app, $stream));
            } catch (\Throwable $e) {
                // Event dispatcher not available (e.g., in unit tests)
            }
        }

        return $success;
    }

    /**
     * Unpublish a stream.
     */
    public function unpublish(string $app, string $stream): bool
    {
        $response = $this->client->post('/manage/publish', [
            'app' => $app,
            'stream' => $stream,
            'action' => 'unpublish',
        ]);

        $success = $response->get('success', false) === true;

        if ($success) {
            try {
                event(new StreamUnpublished($app, $stream));
            } catch (\Throwable $e) {
                // Event dispatcher not available (e.g., in unit tests)
            }
        }

        return $success;
    }

    /**
     * Get stream statistics.
     */
    public function statistics(string $streamId): array
    {
        $response = $this->client->get("/manage/stream/{$streamId}/stats");

        return $response->data();
    }

    /**
     * Get real-time statistics for a specific live stream.
     *
     * Returns detailed information including bandwidth, resolution, codecs,
     * protocol, publisher info, and more for an active stream.
     *
     * @param string $streamName The stream name/key
     * @return StreamStatsDto|null Returns null if stream is not currently live
     */
    public function liveStatus(string $streamName): ?StreamStatsDto
    {
        $response = $this->client->get('/manage/live_streams_status');

        $streams = $response->get('streams', []);

        foreach ($streams as $streamData) {
            if (($streamData['stream_name'] ?? $streamData['name'] ?? null) === $streamName) {
                return StreamStatsDto::fromArray($streamData);
            }
        }

        return null;
    }

    /**
     * Get real-time statistics for all currently active streams.
     *
     * Returns a collection of StreamStatsDto objects containing detailed
     * information for all streams that are currently publishing.
     *
     * @return Collection<int, StreamStatsDto>
     */
    public function allLiveStreams(): Collection
    {
        $response = $this->client->get('/manage/live_streams_status');

        $streams = $response->get('streams', []);

        return collect($streams)->map(function (array $streamData) {
            return StreamStatsDto::fromArray($streamData);
        });
    }
}
