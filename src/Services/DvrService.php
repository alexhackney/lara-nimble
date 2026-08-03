<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Services;

use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\DvrStreamDto;
use Illuminate\Support\Collection;

/**
 * Service for managing Nimble DVR archives.
 */
class DvrService
{
    public function __construct(
        private readonly NimbleClient $client
    ) {}

    /**
     * Get DVR archive status, optionally scoped to one application/stream.
     *
     * @param  bool  $timeline  Include recorded period timelines
     * @return Collection<int, DvrStreamDto>
     */
    public function status(?string $app = null, ?string $stream = null, bool $timeline = false): Collection
    {
        $endpoint = '/manage/dvr_status';

        if ($app !== null && $stream !== null) {
            $endpoint .= "/{$app}/{$stream}";
        }

        $response = $this->client->get($endpoint, $timeline ? ['timeline' => 'true'] : []);

        /** @var array<int, array<string, mixed>> $entries */
        $entries = $response->data();

        return collect($entries)->map(function (array $entry) {
            return DvrStreamDto::fromArray($entry);
        });
    }

    /**
     * Build a ready-to-use MP4 export URL for a DVR range, including
     * authentication parameters when a management token is configured.
     *
     * @param  int|null  $start  Range start as unix timestamp
     * @param  int|null  $end  Range end as unix timestamp
     */
    public function exportMp4Url(string $app, string $stream, ?int $start = null, ?int $end = null): string
    {
        return $this->client->url(
            "/manage/dvr/export_mp4/{$app}/{$stream}",
            array_filter(['start' => $start, 'end' => $end], fn (?int $v) => $v !== null)
        );
    }

    /**
     * Download an MP4 export of a DVR range.
     *
     * Returns the raw MP4 bytes; prefer exportMp4Url() for large ranges so
     * the file streams directly to the consumer instead of through PHP.
     */
    public function exportMp4(string $app, string $stream, ?int $start = null, ?int $end = null): string
    {
        $response = $this->client->get(
            "/manage/dvr/export_mp4/{$app}/{$stream}",
            array_filter(['start' => $start, 'end' => $end], fn (?int $v) => $v !== null)
        );

        return $response->body();
    }

    /**
     * Stream an MP4 export of a DVR range straight to a local file.
     *
     * Uses a streaming download (no in-memory buffering) with no request
     * timeout by default, so long archives export reliably.
     *
     * @param  int|null  $timeout  Seconds; null means no timeout
     */
    public function exportMp4ToFile(
        string $app,
        string $stream,
        string $path,
        ?int $start = null,
        ?int $end = null,
        ?int $timeout = null,
    ): bool {
        $response = $this->client->download(
            "/manage/dvr/export_mp4/{$app}/{$stream}",
            array_filter(['start' => $start, 'end' => $end], fn (?int $v) => $v !== null),
            $path,
            $timeout
        );

        return $response->successful();
    }

    /**
     * Build a ready-to-use SRT subtitle export URL for a DVR range.
     */
    public function exportSrtUrl(
        string $app,
        string $stream,
        ?int $start = null,
        ?int $end = null,
        ?int $track = null,
        ?string $lang = null,
    ): string {
        return $this->client->url(
            "/manage/dvr/export_srt/{$app}/{$stream}",
            array_filter(
                ['start' => $start, 'end' => $end, 'track' => $track, 'lang' => $lang],
                fn (int|string|null $v) => $v !== null
            )
        );
    }

    /**
     * Download SRT subtitles extracted from a DVR range.
     *
     * Returns the raw SRT file contents.
     */
    public function exportSrt(
        string $app,
        string $stream,
        ?int $start = null,
        ?int $end = null,
        ?int $track = null,
        ?string $lang = null,
    ): string {
        $response = $this->client->get(
            "/manage/dvr/export_srt/{$app}/{$stream}",
            array_filter(
                ['start' => $start, 'end' => $end, 'track' => $track, 'lang' => $lang],
                fn (int|string|null $v) => $v !== null
            )
        );

        return $response->body();
    }

    /**
     * Reload a stream's DVR archive from disk.
     */
    public function reload(string $app, string $stream): bool
    {
        $response = $this->client->post("/manage/dvr/reload/{$app}/{$stream}");

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }

    /**
     * Remove recorded data from a stream's DVR archive.
     *
     * With no arguments the whole archive is cleaned up. Use targetDepth to
     * keep the most recent N minutes, or from/to to remove a specific range.
     *
     * @param  int|null  $targetDepth  Minutes of archive to keep
     * @param  int|null  $from  Range start as unix timestamp
     * @param  int|null  $to  Range end as unix timestamp
     */
    public function cleanupArchive(
        string $app,
        string $stream,
        ?int $targetDepth = null,
        ?int $from = null,
        ?int $to = null,
    ): bool {
        $params = array_filter(
            ['target_depth' => $targetDepth, 'from' => $from, 'to' => $to],
            fn (?int $v) => $v !== null
        );

        $response = $this->client->post("/manage/dvr/cleanup_archive/{$app}/{$stream}", [], $params);

        return strcasecmp((string) $response->get('status', ''), 'ok') === 0;
    }
}
