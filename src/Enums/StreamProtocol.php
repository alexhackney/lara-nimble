<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Enums;

/**
 * Streaming protocol types supported by Nimble Streamer.
 */
enum StreamProtocol: string
{
    case RTMP = 'rtmp';
    case MPEG_TS = 'mpegts';
    case SRT = 'srt';
    case NDI = 'ndi';
    case HLS = 'hls';
    case RTSP = 'rtsp';
}
