<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\DTOs\PaymentListFilters;
use YasserElgammal\TabbyPhp\DTOs\PaymentUpdateData;
use YasserElgammal\TabbyPhp\DTOs\TabbyAddressData;
use YasserElgammal\TabbyPhp\DTOs\TabbyBuyerData;
use YasserElgammal\TabbyPhp\DTOs\TabbyItemData;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class DtoValidationTest extends TestCase
{
    public function test_item_dto_serializes_optional_fields(): void
    {
        $item = new TabbyItemData(
            title: 'Product',
            quantity: 2,
            unitPrice: '12.500',
            referenceId: 'sku-1',
            isRefundable: false,
            description: 'Description',
            discountAmount: '2.000',
            imageUrl: 'https://shop.test/product.jpg',
        );

        self::assertSame([
            'title' => 'Product',
            'quantity' => 2,
            'unit_price' => '12.500',
            'discount_amount' => '2.000',
            'reference_id' => 'sku-1',
            'is_refundable' => false,
            'description' => 'Description',
            'image_url' => 'https://shop.test/product.jpg',
        ], $item->toArray());
    }

    public function test_buyer_and_address_serialize_valid_data(): void
    {
        self::assertSame([
            'phone' => '500000000',
            'email' => 'buyer@example.com',
            'name' => 'Buyer',
            'dob' => '1990-01-31',
        ], (new TabbyBuyerData('500000000', 'buyer@example.com', 'Buyer', '1990-01-31'))->toArray());

        self::assertSame([
            'city' => 'Riyadh',
            'address' => 'Street 1',
            'zip' => '12345',
        ], (new TabbyAddressData('Riyadh', 'Street 1', '12345'))->toArray());
    }

    #[DataProvider('invalidFilterProvider')]
    public function test_payment_filters_reject_invalid_boundaries(array $arguments): void
    {
        $this->expectException(ValidationException::class);

        (new PaymentListFilters(...$arguments))->toArray();
    }

    public static function invalidFilterProvider(): array
    {
        return [
            'invalid date' => [['2026/01/01']],
            'limit below minimum' => [[null, null, 0]],
            'limit above maximum' => [[null, null, 101]],
            'negative offset' => [[null, null, 20, -1]],
        ];
    }

    public function test_payment_update_dto_serializes_reference(): void
    {
        self::assertSame(
            ['order' => ['reference_id' => 'order-2']],
            (new PaymentUpdateData('order-2'))->toArray(),
        );
    }

    public function test_invalid_buyer_email_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new TabbyBuyerData('500000000', 'invalid-email', 'Buyer'))->toArray();
    }

    public function test_invalid_address_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new TabbyAddressData('', 'Street 1'))->toArray();
    }
}
