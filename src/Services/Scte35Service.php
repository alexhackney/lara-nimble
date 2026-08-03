<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;

/**
 * Service for SCTE-35 ad insertion markers (/manage/advertizer).
 *
 * Requires the Nimble Advertizer feature.
 */
class Scte35Service
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Insert a cue-out (ad break start) marker.
     *
     * @param  int|null  $duration  Break duration in seconds
     */
    public function cueOut(string $app, string $stream, ?int $duration = null): bool
    {
        $response = $this->client->post(
            "/manage/advertizer/scte35_out/{$app}/{$stream}",
            [],
            $duration !== null ? ['duration' => $duration] : []
        );

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }

    /**
     * Insert a cue-in (ad break end) marker.
     */
    public function cueIn(string $app, string $stream): bool
    {
        $response = $this->client->post("/manage/advertizer/scte35_in/{$app}/{$stream}");

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }

    /**
     * Insert a time_signal marker with segmentation descriptors.
     */
    public function timeSignal(string $app, string $stream, int $segType, int $upidType, string $upid): bool
    {
        $response = $this->client->post(
            "/manage/advertizer/scte35_time_signal/{$app}/{$stream}",
            [],
            ['seg_type' => $segType, 'upid_type' => $upidType, 'upid' => $upid]
        );

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }
}
