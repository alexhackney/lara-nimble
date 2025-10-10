<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble restream targets.
 */
class RestreamService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {
    }

    /**
     * List all restream targets.
     *
     * @return Collection<int, RestreamDto>
     */
    public function list(): Collection
    {
        $response = $this->client->get('/manage/restream/targets');

        $restreams = $response->get('restreams', []);

        return collect($restreams)->map(function (array $restreamData) {
            return RestreamDto::fromArray($restreamData);
        });
    }

    /**
     * Get details of a specific restream target.
     */
    public function get(string $restreamId): RestreamDto
    {
        $response = $this->client->get("/manage/restream/target/{$restreamId}");

        return RestreamDto::fromArray($response->data());
    }

    /**
     * Add a new restream target.
     */
    public function add(string $streamId, array $config): bool
    {
        $response = $this->client->post('/manage/restream/target', array_merge(
            ['stream_id' => $streamId],
            $config
        ));

        return $response->get('success', false) === true;
    }

    /**
     * Update an existing restream target.
     */
    public function update(string $restreamId, array $config): bool
    {
        $response = $this->client->put("/manage/restream/target/{$restreamId}", $config);

        return $response->get('success', false) === true;
    }

    /**
     * Delete a restream target.
     */
    public function delete(string $restreamId): bool
    {
        $response = $this->client->delete("/manage/restream/target/{$restreamId}");

        return $response->get('success', false) === true;
    }
}
