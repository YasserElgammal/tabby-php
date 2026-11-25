<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YasserElgammal\TabbyPhp\Client\ApiClient;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Exceptions\TransportException;
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
            new TransportException('Temporary failure'),
            new Response(200, [], '{"id":"payment-1"}'),
        );
        $client = new ApiClient(new Configuration('secret', retryTimes: 1, retrySleep: 0), $http);

        self::assertSame('payment-1', $client->get('/api/v2/payments/payment-1')['id']);
        self::assertCount(2, $http->requests);
    }

    public function test_get_retries_retryable_server_error(): void
    {
        $http = new FakeHttpClient(
            new Response(503, [], '{"error":"Temporary failure"}'),
            new Response(200, [], '{"id":"payment-1"}'),
        );
        $client = new ApiClient(new Configuration('secret', retryTimes: 1, retrySleep: 0), $http);

        self::assertSame('payment-1', $client->get('/api/v2/payments/payment-1')['id']);
        self::assertCount(2, $http->requests);
    }

    public function test_get_propagates_other_transport_client_exceptions_without_retrying(): void
    {
        $error = new RuntimeException('Unexpected client failure');
        $http = new FakeHttpClient($error, new Response(200, [], '{}'));
        $client = new ApiClient(new Configuration('secret', retryTimes: 1, retrySleep: 0), $http);

        try {
            $client->get('/api/v2/payments/payment-1');
            self::fail('The client exception was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame($error, $exception);
            self::assertCount(1, $http->requests);
        }
    }

    public function test_get_retries_rate_limit_when_retry_after_is_zero(): void
    {
        $http = new FakeHttpClient(
            new Response(429, ['retry-after' => ['0']], '{"error":"Rate limited"}'),
            new Response(200, [], '{"id":"payment-1"}'),
        );
        $client = new ApiClient(new Configuration('secret', retryTimes: 1), $http);

        self::assertSame('payment-1', $client->get('/api/v2/payments/payment-1')['id']);
        self::assertCount(2, $http->requests);
    }

    public function test_get_returns_rate_limit_error_without_retrying_when_retry_after_exceeds_limit(): void
    {
        $http = new FakeHttpClient(
            new Response(429, ['Retry-After' => ['3']], '{"error":"Rate limited"}'),
            new Response(200, [], '{}'),
        );
        $client = new ApiClient(new Configuration('secret', retryTimes: 1, retrySleep: 0), $http);

        try {
            $client->get('/api/v2/payments/payment-1');
            self::fail('An ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame(429, $exception->getCode());
            self::assertCount(1, $http->requests);
        }
    }

    public function test_get_does_not_retry_client_error(): void
    {
        $http = new FakeHttpClient(new Response(404, [], '{"error":"Not found"}'));
        $client = new ApiClient(new Configuration('secret', retryTimes: 2, retrySleep: 0), $http);

        try {
            $client->get('/api/v2/payments/missing');
            self::fail('An ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame(404, $exception->getCode());
            self::assertCount(1, $http->requests);
        }
    }

    public function test_invalid_json_is_wrapped_in_api_exception(): void
    {
        $client = new ApiClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(new Response(200, [], '{invalid')),
        );

        try {
            $client->get('/api/v2/payments/payment-1');
            self::fail('An ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame('Tabby returned an invalid JSON response.', $exception->getMessage());
            self::assertSame(['body' => '{invalid'], $exception->response);
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    public function test_scalar_json_is_rejected_as_an_invalid_api_response(): void
    {
        $client = new ApiClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(new Response(200, [], 'true')),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('invalid JSON response');

        $client->get('/api/v2/payments/payment-1');
    }

    public function test_invalid_error_json_is_exposed_as_raw_body(): void
    {
        $client = new ApiClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(new Response(500, [], '<html>failure</html>')),
        );

        try {
            $client->get('/api/v2/payments/payment-1');
            self::fail('An ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame(['body' => '<html>failure</html>'], $exception->response);
        }
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
