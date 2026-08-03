<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\RestreamDto;
use AlexHackney\LaraNimble\DTOs\RestreamStatsDto;
use AlexHackney\LaraNimble\Exceptions\NimbleApiException;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble RTMP republishing rules.
 *
 * Uses the native API at /manage/rtmp/republish. Two caveats from the
 * Nimble docs: only rules created through this API appear in list(), so
 * rules made in WMSPanel are invisible here, and API-created rules are
 * not persisted across a Nimble config reload or restart.
 */
class RestreamService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * List all republishing rules created through the native API.
     *
     * @return Collection<int, RestreamDto>
     */
    public function list(): Collection
    {
        $response = $this->client->get('/manage/rtmp/republish');

        /** @var array<int, array<string, mixed>> $rules */
        $rules = $response->get('rules', []);

        return collect($rules)->map(function (array $rule) {
            return RestreamDto::fromArray($rule);
        });
    }

    /**
     * Get a specific republishing rule, or null when it does not exist.
     */
    public function get(int|string $ruleId): ?RestreamDto
    {
        $response = $this->client->get("/manage/rtmp/republish/{$ruleId}");

        /** @var array<string, mixed>|null $rule */
        $rule = $response->get('rule') ?? $response->get('rules.0');

        return is_array($rule) ? RestreamDto::fromArray($rule) : null;
    }

    /**
     * Create a new republishing rule and return it with its assigned id.
     *
     * @throws NimbleApiException when Nimble rejects the rule
     */
    public function create(RestreamDto $rule): RestreamDto
    {
        $response = $this->client->post('/manage/rtmp/republish', $rule->toCreatePayload());

        /** @var array<string, mixed>|null $created */
        $created = $response->get('rule');

        if (! is_array($created)) {
            throw new NimbleApiException(
                'Nimble did not return the created republishing rule',
                $response->statusCode(),
                $response->data()
            );
        }

        return RestreamDto::fromArray($created);
    }

    /**
     * Delete a republishing rule.
     */
    public function delete(int|string $ruleId): bool
    {
        $response = $this->client->delete("/manage/rtmp/republish/{$ruleId}");

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }

    /**
     * Get connection statistics for all republishing rules.
     *
     * @return Collection<int, RestreamStatsDto>
     */
    public function stats(): Collection
    {
        $response = $this->client->get('/manage/rtmp/republish/stats');

        /** @var array<int, array<string, mixed>> $stats */
        $stats = $response->get('stats', []);

        return collect($stats)->map(function (array $entry) {
            return RestreamStatsDto::fromArray($entry);
        });
    }
}
