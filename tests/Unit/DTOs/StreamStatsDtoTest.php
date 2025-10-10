<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\DTOs;

use AlexHackney\LaraNimble\DTOs\StreamStatsDto;
use PHPUnit\Framework\TestCase;

class StreamStatsDtoTest extends TestCase
{
    /** @test */
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'stream_name' => 'my-stream',
            'application' => 'live',
            'bandwidth' => 5000000,
            'resolution' => '1920x1080',
            'vcodec' => 'h264',
            'acodec' => 'aac',
            'protocol' => 'rtmp',
            'source_url' => 'rtmp://source.com/live/stream',
            'publisher_ip' => '192.168.1.100',
            'publisher_port' => 1935,
            'viewers' => 42,
            'fps' => 30.0,
            'bitrate' => 2500,
            'duration' => 3600,
            'start_time' => '2024-01-01 12:00:00',
        ];

        $dto = StreamStatsDto::fromArray($data);

        $this->assertEquals('my-stream', $dto->streamName);
        $this->assertEquals('live', $dto->application);
        $this->assertEquals(5000000, $dto->bandwidth);
        $this->assertEquals('1920x1080', $dto->resolution);
        $this->assertEquals('h264', $dto->videoCodec);
        $this->assertEquals('aac', $dto->audioCodec);
        $this->assertEquals('rtmp', $dto->protocol);
        $this->assertEquals('rtmp://source.com/live/stream', $dto->sourceUrl);
        $this->assertEquals('192.168.1.100', $dto->publisherIp);
        $this->assertEquals(1935, $dto->publisherPort);
        $this->assertEquals(42, $dto->viewers);
        $this->assertEquals(30.0, $dto->fps);
        $this->assertEquals(2500, $dto->bitrate);
        $this->assertEquals(3600, $dto->duration);
        $this->assertEquals('2024-01-01 12:00:00', $dto->startTime);
    }

    /** @test */
    public function it_can_handle_optional_fields(): void
    {
        $data = [
            'stream_name' => 'minimal-stream',
        ];

        $dto = StreamStatsDto::fromArray($data);

        $this->assertEquals('minimal-stream', $dto->streamName);
        $this->assertNull($dto->application);
        $this->assertNull($dto->bandwidth);
        $this->assertNull($dto->resolution);
        $this->assertNull($dto->videoCodec);
        $this->assertNull($dto->audioCodec);
        $this->assertNull($dto->protocol);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        $dto = new StreamStatsDto(
            streamName: 'test-stream',
            application: 'live',
            bandwidth: 5000000,
            resolution: '1920x1080',
            videoCodec: 'h264',
            audioCodec: 'aac',
            protocol: 'rtmp',
            sourceUrl: 'rtmp://source.com/live/stream',
            publisherIp: '192.168.1.100',
            publisherPort: 1935,
            viewers: 42,
            fps: 30.0,
            bitrate: 2500,
            duration: 3600,
            startTime: '2024-01-01 12:00:00',
        );

        $array = $dto->toArray();

        $this->assertEquals('test-stream', $array['stream_name']);
        $this->assertEquals('live', $array['application']);
        $this->assertEquals(5000000, $array['bandwidth']);
        $this->assertEquals('1920x1080', $array['resolution']);
        $this->assertEquals('h264', $array['video_codec']);
        $this->assertEquals('aac', $array['audio_codec']);
        $this->assertEquals('rtmp', $array['protocol']);
        $this->assertEquals('rtmp://source.com/live/stream', $array['source_url']);
        $this->assertEquals('192.168.1.100', $array['publisher_ip']);
        $this->assertEquals(1935, $array['publisher_port']);
        $this->assertEquals(42, $array['viewers']);
        $this->assertEquals(30.0, $array['fps']);
        $this->assertEquals(2500, $array['bitrate']);
        $this->assertEquals(3600, $array['duration']);
        $this->assertEquals('2024-01-01 12:00:00', $array['start_time']);
    }

    /** @test */
    public function it_handles_alternative_field_names_from_api(): void
    {
        // Test with 'name' instead of 'stream_name'
        $data1 = [
            'name' => 'stream-with-name',
            'protocol' => 'srt',
        ];

        $dto1 = StreamStatsDto::fromArray($data1);
        $this->assertEquals('stream-with-name', $dto1->streamName);

        // Test with 'app' instead of 'application'
        $data2 = [
            'stream_name' => 'test',
            'app' => 'live-app',
        ];

        $dto2 = StreamStatsDto::fromArray($data2);
        $this->assertEquals('live-app', $dto2->application);

        // Test with 'video_codec' instead of 'vcodec'
        $data3 = [
            'stream_name' => 'test',
            'video_codec' => 'h265',
            'audio_codec' => 'opus',
        ];

        $dto3 = StreamStatsDto::fromArray($data3);
        $this->assertEquals('h265', $dto3->videoCodec);
        $this->assertEquals('opus', $dto3->audioCodec);
    }
}
