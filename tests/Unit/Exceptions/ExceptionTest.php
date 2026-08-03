<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Exceptions;

use AlexHackney\LaraNimble\Exceptions\InvalidConfigurationException;
use AlexHackney\LaraNimble\Exceptions\NimbleApiException;
use AlexHackney\LaraNimble\Exceptions\NimbleAuthenticationException;
use AlexHackney\LaraNimble\Exceptions\NimbleConnectionException;
use AlexHackney\LaraNimble\Exceptions\NimbleException;
use AlexHackney\LaraNimble\Exceptions\StreamNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    #[Test]
    public function nimble_exception_can_be_instantiated(): void
    {
        $exception = new NimbleException('Test error');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test error', $exception->getMessage());
    }

    #[Test]
    public function nimble_connection_exception_extends_nimble_exception(): void
    {
        $exception = new NimbleConnectionException('Connection failed');

        $this->assertInstanceOf(NimbleException::class, $exception);
        $this->assertEquals('Connection failed', $exception->getMessage());
    }

    #[Test]
    public function nimble_authentication_exception_extends_nimble_exception(): void
    {
        $exception = new NimbleAuthenticationException('Auth failed');

        $this->assertInstanceOf(NimbleException::class, $exception);
        $this->assertEquals('Auth failed', $exception->getMessage());
    }

    #[Test]
    public function nimble_api_exception_extends_nimble_exception(): void
    {
        $exception = new NimbleApiException('API error', 500);

        $this->assertInstanceOf(NimbleException::class, $exception);
        $this->assertEquals('API error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    #[Test]
    public function stream_not_found_exception_extends_nimble_exception(): void
    {
        $exception = new StreamNotFoundException('Stream not found');

        $this->assertInstanceOf(NimbleException::class, $exception);
        $this->assertEquals('Stream not found', $exception->getMessage());
    }

    #[Test]
    public function invalid_configuration_exception_extends_nimble_exception(): void
    {
        $exception = new InvalidConfigurationException('Invalid config');

        $this->assertInstanceOf(NimbleException::class, $exception);
        $this->assertEquals('Invalid config', $exception->getMessage());
    }

    #[Test]
    public function exceptions_can_have_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new NimbleException('Current error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function exceptions_can_have_custom_codes(): void
    {
        $exception = new NimbleException('Error with code', 404);

        $this->assertEquals(404, $exception->getCode());
    }
}
