<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use YasserElgammal\TabbyPhp\DTOs\PaymentListFilters;
use YasserElgammal\TabbyPhp\DTOs\PaymentUpdateData;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Tests\Support\FakeHttpClient;

final class PaymentResourceTest extends TestCase
{
    public function test_get_and_get_response_validate_and_map_payment(): void
    {
        $http = new FakeHttpClient(
            new Response(200, [], '{"id":"payment-1","status":"AUTHORIZED","amount":"100.00"}'),
            new Response(200, [], '{"id":"payment-1","status":"AUTHORIZED","amount":"100.00"}'),
        );
        $payments = (new TabbyClient(new Configuration('secret', retryTimes: 0), $http))->payments();

        self::assertSame('payment-1', $payments->get('payment-1')['id']);

        $response = $payments->getResponse('payment-1');
        self::assertSame('payment-1', $response->id);
        self::assertSame('AUTHORIZED', $response->status);
        self::assertSame('100.00', $response->amount);
    }

    public function test_update_accepts_dto_and_list_serializes_filters(): void
    {
        $http = new FakeHttpClient(
            new Response(200, [], '{}'),
            new Response(200, [], '{"payments":[]}'),
        );
        $payments = (new TabbyClient(new Configuration('secret', retryTimes: 0), $http))->payments();

        $payments->update('payment-1', new PaymentUpdateData('order-2'));
        $payments->all(new PaymentListFilters('2026-01-01', '2026-01-31', 10, 5));

        self::assertSame(['order' => ['reference_id' => 'order-2']], $http->requests[0]['options']['json']);
        self::assertSame([
            'created_at__gte' => '2026-01-01',
            'created_at__lte' => '2026-01-31',
            'limit' => 10,
            'offset' => 5,
        ], $http->requests[1]['options']['query']);
    }

    public function test_full_capture_subtracts_existing_captures(): void
    {
        $http = new FakeHttpClient(
            new Response(200, [], '{"id":"payment-1","amount":"100.000","captures":[{"amount":"20.250"}]}'),
            new Response(200, [], '{}'),
        );
        $payments = (new TabbyClient(new Configuration('secret', retryTimes: 0), $http))->payments();

        $payments->capture('payment-1', 'capture-1');

        self::assertSame('79.750', $http->requests[1]['options']['json']['amount']);
        self::assertSame('capture-1', $http->requests[1]['options']['json']['reference_id']);
    }

    public function test_refund_and_close_use_expected_endpoints_and_payloads(): void
    {
        $http = new FakeHttpClient(
            new Response(200, [], '{}'),
            new Response(200, [], '{}'),
        );
        $payments = (new TabbyClient(new Configuration('secret', retryTimes: 0), $http))->payments();

        $payments->refund('payment-1', 'refund-1', '25.50', 'Returned');
        $payments->close('payment-1');

        self::assertSame('https://api.tabby.sa/api/v2/payments/payment-1/refunds', $http->requests[0]['uri']);
        self::assertSame([
            'amount' => '25.50',
            'reference_id' => 'refund-1',
            'reason' => 'Returned',
        ], $http->requests[0]['options']['json']);
        self::assertSame('https://api.tabby.sa/api/v2/payments/payment-1/close', $http->requests[1]['uri']);
    }

    public function test_payment_mutation_rejects_empty_reference(): void
    {
        $payments = (new TabbyClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(),
        ))->payments();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('non-empty reference ID');

        $payments->capture('payment-1', '');
    }

    public function test_payment_mutation_rejects_non_positive_amount(): void
    {
        $payments = (new TabbyClient(
            new Configuration('secret', retryTimes: 0),
            new FakeHttpClient(),
        ))->payments();

        $this->expectException(ValidationException::class);

        $payments->refund('payment-1', 'refund-1', '0');
    }
}
