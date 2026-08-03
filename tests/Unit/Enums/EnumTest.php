<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Enums;

use AlexHackney\LaraNimble\Enums\PublishAction;
use AlexHackney\LaraNimble\Enums\StreamProtocol;
use AlexHackney\LaraNimble\Enums\StreamStatus;
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
    public function stream_status_enum_has_expected_cases(): void
    {
        $this->assertEquals('active', StreamStatus::ACTIVE->value);
        $this->assertEquals('inactive', StreamStatus::INACTIVE->value);
        $this->assertEquals('error', StreamStatus::ERROR->value);
    }

    #[Test]
    public function stream_status_enum_has_all_cases(): void
    {
        $cases = StreamStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContainsOnlyInstancesOf(StreamStatus::class, $cases);
    }

    #[Test]
    public function publish_action_enum_has_expected_cases(): void
    {
        $this->assertEquals('publish', PublishAction::PUBLISH->value);
        $this->assertEquals('unpublish', PublishAction::UNPUBLISH->value);
    }

    #[Test]
    public function publish_action_enum_has_all_cases(): void
    {
        $cases = PublishAction::cases();

        $this->assertCount(2, $cases);
        $this->assertContainsOnlyInstancesOf(PublishAction::class, $cases);
    }

    #[Test]
    public function stream_protocol_can_be_created_from_string(): void
    {
        $protocol = StreamProtocol::from('rtmp');

        $this->assertEquals(StreamProtocol::RTMP, $protocol);
    }

    #[Test]
    public function stream_status_can_be_created_from_string(): void
    {
        $status = StreamStatus::from('active');

        $this->assertEquals(StreamStatus::ACTIVE, $status);
    }

    #[Test]
    public function publish_action_can_be_created_from_string(): void
    {
        $action = PublishAction::from('publish');

        $this->assertEquals(PublishAction::PUBLISH, $action);
    }
}
