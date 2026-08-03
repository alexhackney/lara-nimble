<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;

/**
 * Service for protocol-specific status and settings endpoints.
 *
 * These endpoints return structures defined by Nimble or by the
 * respective protocol specifications (SRT, RIST), so they are exposed
 * as raw arrays rather than DTOs.
 */
class ProtocolService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get RTMP interface settings (RtmpSettings structure).
     */
    public function rtmpSettings(): array
    {
        $response = $this->client->get('/manage/rtmp_settings');

        return $response->data();
    }

    /**
     * Get MPEG-TS stream status.
     */
    public function mpegtsStatus(): array
    {
        $response = $this->client->get('/manage/mpeg2ts_status');

        return $response->data();
    }

    /**
     * Get MPEG-TS settings (cameras configuration).
     */
    public function mpegtsSettings(): array
    {
        $response = $this->client->get('/manage/mpeg2ts_settings');

        return $response->data();
    }

    /**
     * Get SRT sender statistics (fields defined by the SRT spec).
     */
    public function srtSenderStats(): array
    {
        $response = $this->client->get('/manage/srt_sender_stats');

        return $response->data();
    }

    /**
     * Get SRT receiver statistics (fields defined by the SRT spec).
     */
    public function srtReceiverStats(): array
    {
        $response = $this->client->get('/manage/srt_receiver_stats');

        return $response->data();
    }

    /**
     * Get RIST sender statistics (fields defined by the RIST spec).
     */
    public function ristSenderStats(): array
    {
        $response = $this->client->get('/manage/rist_sender_stats');

        return $response->data();
    }

    /**
     * Get RIST receiver statistics (fields defined by the RIST spec).
     */
    public function ristReceiverStats(): array
    {
        $response = $this->client->get('/manage/rist_receiver_stats');

        return $response->data();
    }

    /**
     * List available NDI streams.
     */
    public function ndiList(): array
    {
        $response = $this->client->get('/manage/ndi/list');

        return $response->data();
    }
}
