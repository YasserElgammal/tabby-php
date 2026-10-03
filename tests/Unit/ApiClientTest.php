<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YasserElgammal\TabbyPhp\Client\ApiClient;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Tests\Support\FakeHttpClient;

final class ApiClientTest extends TestCase
{
    public function test_request_builds_url_headers_and_timeouts(): void
    {
        $http = new FakeHttpClient(new Response(200, [], '{"id":"payment-1"}'));
        $client = new ApiClient(new Configuration(
            secretKey: 'secret',
            baseUrl: 'https://example.test/api/v2/',
            merchantCode: 'merchant-1',
            timeout: 20,
            connectTimeout: 7,
            retryTimes: 0,
        ), $http);

        $result = $client->post('/api/v2/payments', ['amount' => '10.00'], true);

        self::assertSame('payment-1', $result['id']);
        self::assertSame('POST', $http->requests[0]['method']);
        self::assertSame('https://example.test/api/v2/payments', $http->requests[0]['uri']);
        self::assertSame('Bearer secret', $http->requests[0]['options']['headers']['Authorization']);
        self::assertSame('merchant-1', $http->requests[0]['options']['headers']['X-Merchant-Code']);
        self::assertSame('application/json', $http->requests[0]['options']['headers']['Content-Type']);
        self::assertSame(20, $http->requests[0]['options']['timeout']);
        self::assertSame(7, $http->requests[0]['options']['connect_timeout']);
    }

    public function test_api_error_contains_status_and_response_payload(): void
    {
        $http = new FakeHttpClient(new Response(422, [], '{"error":"Invalid payment"}'));
        $client = new ApiClient(new Configuration('secret', retryTimes: 0), $http);

        try {
            $client->get('/api/v2/payments/payment-1');
            self::fail('An ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->getCode());
            self::assertSame('Invalid payment', $exception->getMessage());
            self::assertSame(['error' => 'Invalid payment'], $exception->response);
        }
    }

    public function test_get_retries_after_transport_failure(): void
    {
        $http = new FakeHttpClient(
            new RuntimeException('Temporary failure'),
            new Response(200, [], '{"id":"payment-1"}'),
        );
        $client = new ApiClient(new Configuration('secret', retryTimes: 1, retrySleep: 0), $http);

        self::assertSame('payment-1', $client->get('/api/v2/payments/payment-1')['id']);
        self::assertCount(2, $http->requests);
    }

    public function test_merchant_header_requires_merchant_code(): void
    {
        $client = new ApiClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(new Response(200, [], '{}')),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('merchant code is not configured');

        $client->get('/api/v1/webhooks', [], true);
    }
}
