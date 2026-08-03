<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Enums;

use AlexHackney\LaraNimble\Enums\StreamProtocol;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    #[Test]
    public function stream_protocol_enum_has_expected_cases(): void
    {
        $this->assertEquals('rtmp', StreamProtocol::RTMP->value);
        $this->assertEquals('mpegts', StreamProtocol::MPEG_TS->value);
        $this->assertEquals('srt', StreamProtocol::SRT->value);
        $this->assertEquals('ndi', StreamProtocol::NDI->value);
        $this->assertEquals('hls', StreamProtocol::HLS->value);
        $this->assertEquals('rtsp', StreamProtocol::RTSP->value);
    }

    #[Test]
    public function stream_protocol_enum_has_all_cases(): void
    {
        $cases = StreamProtocol::cases();

        $this->assertCount(6, $cases);
        $this->assertContainsOnlyInstancesOf(StreamProtocol::class, $cases);
    }

    #[Test]
    public function stream_protocol_can_be_created_from_string(): void
    {
        $protocol = StreamProtocol::from('rtmp');

        $this->assertEquals(StreamProtocol::RTMP, $protocol);
    }
}
