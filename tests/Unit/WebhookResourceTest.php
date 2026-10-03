<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Tests\Support\FakeHttpClient;

final class WebhookResourceTest extends TestCase
{
    public function test_webhook_crud_uses_merchant_header_and_expected_endpoints(): void
    {
        $http = new FakeHttpClient(
            new Response(200, [], '{"id":"webhook-1"}'),
            new Response(200, [], '{"webhooks":[]}'),
            new Response(200, [], '{"id":"webhook-1","url":"https://shop.test/tabby"}'),
            new Response(200, [], '{}'),
            new Response(200, [], '{}'),
        );
        $tabby = new TabbyClient(new Configuration(
            secretKey: 'secret',
            merchantCode: 'merchant-1',
            retryTimes: 0,
        ), $http);
        $webhooks = $tabby->webhooks();

        $webhooks->create('https://shop.test/tabby', headers: ['title' => 'X-Token', 'value' => 'value']);
        $webhooks->all();
        $response = $webhooks->getResponse('webhook-1');
        $webhooks->update('webhook-1', 'https://shop.test/tabby-updated');
        $webhooks->delete('webhook-1');

        self::assertSame('webhook-1', $response->id);
        self::assertSame('https://shop.test/tabby', $response->url);
        self::assertSame(
            ['title' => 'X-Token', 'value' => 'value'],
            $http->requests[0]['options']['json']['header'],
        );
        self::assertSame('PUT', $http->requests[3]['method']);
        self::assertSame('DELETE', $http->requests[4]['method']);

        foreach ($http->requests as $request) {
            self::assertSame('merchant-1', $request['options']['headers']['X-Merchant-Code']);
        }
    }

    public function test_webhook_header_requires_title_and_value(): void
    {
        $webhooks = (new TabbyClient(
            new Configuration('secret', merchantCode: 'merchant-1', retryTimes: 0),
            new FakeHttpClient(),
        ))->webhooks();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('non-empty title and value');

        $webhooks->create('https://shop.test/tabby', headers: ['title' => '', 'value' => 'token']);
    }

    public function test_webhook_secret_is_compared_safely(): void
    {
        $tabby = new TabbyClient(new Configuration('secret', webhookSecret: 'expected-secret'));

        self::assertTrue($tabby->webhookVerifier()->verify('expected-secret'));
        self::assertFalse($tabby->webhookVerifier()->verify('wrong-secret'));
        self::assertFalse($tabby->webhookVerifier()->verify(null));
    }

    public function test_webhook_verifier_requires_configured_secret(): void
    {
        $tabby = new TabbyClient(new Configuration('secret'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('webhook secret is not configured');

        $tabby->webhookVerifier();
    }
}
