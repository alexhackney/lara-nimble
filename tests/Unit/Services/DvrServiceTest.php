<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use AlexHackney\LaraNimble\Client\NimbleClient;
use AlexHackney\LaraNimble\DTOs\ArchiveDto;
use AlexHackney\LaraNimble\Services\DvrService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DvrServiceTest extends TestCase
{
    private function createService(MockHandler $mockHandler): DvrService
    {
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'host' => 'localhost',
            'port' => 8082,
            'protocol' => 'http',
        ];

        $nimbleClient = new NimbleClient($config, $httpClient);

        return new DvrService($nimbleClient);
    }

    /** @test */
    public function it_can_list_archives(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'archives' => [
                    [
                        'id' => 'archive-1',
                        'stream_id' => 'stream-123',
                        'filename' => 'archive1.mp4',
                        'size' => 1234567890,
                        'duration' => 3600,
                    ],
                    [
                        'id' => 'archive-2',
                        'stream_id' => 'stream-456',
                        'filename' => 'archive2.mp4',
                        'size' => 987654321,
                        'duration' => 1800,
                    ],
                ],
            ])),
        ]);

        $service = $this->createService($mock);
        $archives = $service->listArchives();

        $this->assertInstanceOf(Collection::class, $archives);
        $this->assertCount(2, $archives);
        $this->assertContainsOnlyInstancesOf(ArchiveDto::class, $archives);
        $this->assertEquals('archive-1', $archives->first()->id);
    }

    /** @test */
    public function it_can_get_a_specific_archive(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 'archive-789',
                'stream_id' => 'stream-123',
                'filename' => 'archive.mp4',
                'size' => 1234567890,
                'duration' => 3600,
            ])),
        ]);

        $service = $this->createService($mock);
        $archive = $service->getArchive('archive-789');

        $this->assertInstanceOf(ArchiveDto::class, $archive);
        $this->assertEquals('archive-789', $archive->id);
        $this->assertEquals('stream-123', $archive->streamId);
    }

    /** @test */
    public function it_can_delete_an_archive(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'message' => 'Archive deleted',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->deleteArchive('archive-789');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_delete_fails(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Archive not found',
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->deleteArchive('archive-789');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_configure_dvr_settings(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
            ])),
        ]);

        $service = $this->createService($mock);
        $result = $service->configure([
            'stream' => 'stream1',
            'enabled' => true,
            'path' => '/var/dvr/stream1',
        ]);

        $this->assertTrue($result);
    }
}
