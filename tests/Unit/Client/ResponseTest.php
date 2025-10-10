<?php

declare(strict_types=1);

namespace AlexHackney\LaraNimble\Tests\Unit\Client;

use AlexHackney\LaraNimble\Client\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function test_successful_returns_true_for_2xx_status(): void
    {
        $response = new Response(new GuzzleResponse(200));
        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
    }

    public function test_failed_returns_true_for_4xx_status(): void
    {
        $response = new Response(new GuzzleResponse(404));
        $this->assertTrue($response->failed());
        $this->assertFalse($response->successful());
    }

    public function test_client_error_returns_true_for_4xx_status(): void
    {
        $response = new Response(new GuzzleResponse(400));
        $this->assertTrue($response->clientError());
        $this->assertFalse($response->serverError());
    }

    public function test_server_error_returns_true_for_5xx_status(): void
    {
        $response = new Response(new GuzzleResponse(500));
        $this->assertTrue($response->serverError());
        $this->assertFalse($response->clientError());
    }

    public function test_status_code_returns_correct_code(): void
    {
        $response = new Response(new GuzzleResponse(201));
        $this->assertEquals(201, $response->statusCode());
    }

    public function test_data_returns_parsed_json(): void
    {
        $data = ['status' => 'success', 'data' => ['id' => 123]];
        $response = new Response(new GuzzleResponse(200, [], json_encode($data)));

        $this->assertEquals($data, $response->data());
        $this->assertEquals('success', $response->get('status'));
        $this->assertEquals(123, $response->get('data.id'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $response = new Response(new GuzzleResponse(200, [], json_encode(['status' => 'ok'])));

        $this->assertEquals('default', $response->get('missing', 'default'));
        $this->assertNull($response->get('missing'));
    }

    public function test_body_returns_raw_response_body(): void
    {
        $body = '{"status":"ok"}';
        $response = new Response(new GuzzleResponse(200, [], $body));

        $this->assertEquals($body, $response->body());
    }

    public function test_headers_returns_all_headers(): void
    {
        $response = new Response(new GuzzleResponse(200, [
            'Content-Type' => 'application/json',
            'X-Custom-Header' => 'value',
        ]));

        $headers = $response->headers();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals(['application/json'], $headers['Content-Type']);
    }

    public function test_header_returns_specific_header(): void
    {
        $response = new Response(new GuzzleResponse(200, [
            'Content-Type' => 'application/json',
        ]));

        $this->assertEquals('application/json', $response->header('Content-Type'));
        $this->assertNull($response->header('Missing-Header'));
    }

    public function test_to_array_returns_data(): void
    {
        $data = ['status' => 'success'];
        $response = new Response(new GuzzleResponse(200, [], json_encode($data)));

        $this->assertEquals($data, $response->toArray());
    }

    public function test_to_json_returns_json_string(): void
    {
        $data = ['status' => 'success'];
        $response = new Response(new GuzzleResponse(200, [], json_encode($data)));

        $this->assertEquals(json_encode($data), $response->toJson());
    }

    public function test_handles_empty_response_body(): void
    {
        $response = new Response(new GuzzleResponse(204));

        $this->assertEquals([], $response->data());
        $this->assertEquals('', $response->body());
    }

    public function test_handles_invalid_json(): void
    {
        $response = new Response(new GuzzleResponse(200, [], 'invalid json'));

        $this->assertEquals([], $response->data());
        $this->assertEquals('invalid json', $response->body());
    }

    public function test_handles_non_array_json(): void
    {
        $response = new Response(new GuzzleResponse(200, [], json_encode('string value')));

        $this->assertEquals([], $response->data());
    }

    public function test_to_psr_response_returns_underlying_response(): void
    {
        $guzzleResponse = new GuzzleResponse(200);
        $response = new Response($guzzleResponse);

        $this->assertSame($guzzleResponse, $response->toPsrResponse());
    }
}
