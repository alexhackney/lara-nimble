<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\PullDto;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble stream pulling.
 */
class PullService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * List all pull configurations.
     *
     * @return Collection<int, PullDto>
     */
    public function list(): Collection
    {
        $response = $this->client->get('/manage/pull');

        /** @var array<int, array<string, mixed>> $pulls */
        $pulls = $response->get('pulls', []);

        return collect($pulls)->map(function (array $pullData) {
            return PullDto::fromArray($pullData);
        });
    }

    /**
     * Get details of a specific pull configuration.
     */
    public function get(string $pullId): PullDto
    {
        $response = $this->client->get("/manage/pull/{$pullId}");

        return PullDto::fromArray($response->data());
    }

    /**
     * Add a new pull configuration.
     */
    public function add(array $config): bool
    {
        $response = $this->client->post('/manage/pull', $config);

        return $response->get('success', false) === true;
    }

    /**
     * Update an existing pull configuration.
     */
    public function update(string $pullId, array $config): bool
    {
        $response = $this->client->put("/manage/pull/{$pullId}", $config);

        return $response->get('success', false) === true;
    }

    /**
     * Delete a pull configuration.
     */
    public function delete(string $pullId): bool
    {
        $response = $this->client->delete("/manage/pull/{$pullId}");

        return $response->get('success', false) === true;
    }

    /**
     * Get status of a pull stream.
     */
    public function status(string $pullId): array
    {
        $response = $this->client->get("/manage/pull/{$pullId}/status");

        return $response->data();
    }
}
