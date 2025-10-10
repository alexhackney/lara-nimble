<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \AlexHackney\LaraNimble\NimbleServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Nimble' => \AlexHackney\LaraNimble\Facades\Nimble::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration for testing
        config()->set('nimble.host', 'localhost');
        config()->set('nimble.port', 8082);
        config()->set('nimble.protocol', 'http');
        config()->set('nimble.token', 'test-token');
        config()->set('nimble.timeout', 30);
        config()->set('nimble.connect_timeout', 10);
        config()->set('nimble.retry_times', 3);
        config()->set('nimble.retry_sleep', 100);
        config()->set('nimble.log_requests', false);
        config()->set('nimble.cache_enabled', false);
        config()->set('nimble.verify_ssl', true);
    }
}
