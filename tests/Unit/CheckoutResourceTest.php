<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use YasserElgammal\TabbyPhp\DTOs\TabbyAddressData;
use YasserElgammal\TabbyPhp\DTOs\TabbyBuyerData;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Tests\Support\FakeHttpClient;

final class CheckoutResourceTest extends TestCase
{
    public function test_checkout_is_serialized_and_response_is_typed(): void
    {
        $body = json_encode([
            'id' => 'session-1',
            'status' => 'created',
            'payment' => ['id' => 'payment-1'],
            'configuration' => [
                'available_products' => [
                    'installments' => [['web_url' => 'https://checkout.test/session-1']],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        $http = new FakeHttpClient(new Response(200, [], $body));
        $tabby = new TabbyClient(new Configuration('secret', retryTimes: 0), $http);

        $item = $tabby->item()
            ->title('Product')
            ->quantity(1)
            ->unitPrice('100.00')
            ->referenceId('sku-1')
            ->build();
        $checkout = $tabby->checkout()
            ->amount('100.00')
            ->referenceId('order-1')
            ->buyer(new TabbyBuyerData('500000000', 'buyer@example.com', 'Buyer'))
            ->shippingAddress(new TabbyAddressData('Riyadh', 'Street 1'))
            ->item($item)
            ->merchantUrls('https://shop.test/success', 'https://shop.test/cancel', 'https://shop.test/failure')
            ->build();

        $response = $tabby->checkouts()->create($checkout);

        self::assertSame('session-1', $response->sessionId);
        self::assertSame('payment-1', $response->paymentId);
        self::assertSame('created', $response->status);
        self::assertSame('https://checkout.test/session-1', $response->url());
        self::assertTrue($response->isAvailable());
        self::assertSame('100.00', $http->requests[0]['options']['json']['payment']['amount']);
        self::assertSame('order-1', $http->requests[0]['options']['json']['payment']['order']['reference_id']);
    }

    public function test_invalid_checkout_response_is_rejected(): void
    {
        $tabby = new TabbyClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(new Response(200, [], '{}')),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('invalid checkout response');

        $tabby->checkouts()->createFromArray(['payment' => []]);
    }
}
