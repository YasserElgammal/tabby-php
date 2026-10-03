<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Client\Http\GuzzleHttpClient;
use YasserElgammal\TabbyPhp\Exceptions\TransportException;

final class GuzzleHttpClientTest extends TestCase
{
    public function test_guzzle_response_is_converted_to_sdk_response(): void
    {
        $handler = new MockHandler([
            new PsrResponse(200, ['X-Test' => 'yes'], '{"ok":true}'),
        ]);
        $client = new GuzzleHttpClient(new GuzzleClient([
            'handler' => HandlerStack::create($handler),
        ]));

        $response = $client->request('GET', 'https://example.test');

        self::assertSame(200, $response->statusCode);
        self::assertSame(['ok' => true], $response->json());
        self::assertSame(['yes'], $response->headers['X-Test']);
    }

    public function test_connection_failure_becomes_transport_exception(): void
    {
        $handler = new MockHandler([
            new ConnectException('Connection failed', new Request('GET', 'https://example.test')),
        ]);
        $client = new GuzzleHttpClient(new GuzzleClient([
            'handler' => HandlerStack::create($handler),
        ]));

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Unable to connect');

        $client->request('GET', 'https://example.test');
    }

    public function test_http_error_response_is_returned_for_api_handling(): void
    {
        $handler = new MockHandler([
            new PsrResponse(500, [], '{"error":"Server error"}'),
        ]);
        $client = new GuzzleHttpClient(new GuzzleClient([
            'handler' => HandlerStack::create($handler),
        ]));

        $response = $client->request('GET', 'https://example.test');

        self::assertSame(500, $response->statusCode);
        self::assertSame(['error' => 'Server error'], $response->json());
    }
}
