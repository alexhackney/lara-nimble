<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Integration;

use AlexHackney\LaraNimble\Client\NimbleClient;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that run against a real Nimble server.
 *
 * Skipped unless NIMBLE_TEST_HOST is set. Configure via env:
 *
 *   NIMBLE_TEST_HOST=streaming.example.com
 *   NIMBLE_TEST_PORT=8082            (optional)
 *   NIMBLE_TEST_PROTOCOL=http        (optional)
 *   NIMBLE_TEST_TOKEN=secret         (optional, matches management_token)
 *
 * Run with: composer test-integration
 */
abstract class IntegrationTestCase extends TestCase
{
    protected NimbleClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('NIMBLE_TEST_HOST');

        if ($host === false || $host === '') {
            $this->markTestSkipped('Set NIMBLE_TEST_HOST to run integration tests against a real Nimble server.');
        }

        $token = getenv('NIMBLE_TEST_TOKEN');

        $this->client = new NimbleClient([
            'host' => $host,
            'port' => (int) (getenv('NIMBLE_TEST_PORT') ?: 8082),
            'protocol' => getenv('NIMBLE_TEST_PROTOCOL') ?: 'http',
            'token' => $token === false || $token === '' ? null : $token,
        ]);
    }
}
