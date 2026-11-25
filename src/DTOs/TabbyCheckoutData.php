<?php

namespace YasserElgammal\TabbyPhp\DTOs;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Support\ValidatesInput;

final readonly class TabbyCheckoutData
{
    /**
     * @param array<string, mixed> $merchantUrls
     * @param array<string, mixed> $buyerHistory
     * @param list<array<string, mixed>> $orderHistory
     * @param array<string, mixed> $meta
     * @param array{body: string, content_type: string}|null $attachment
     */
    public function __construct(
        public string|int|float $amount,
        public string $currency,
        public TabbyBuyerData $buyer,
        public TabbyAddressData $shippingAddress,
        public TabbyOrderData $order,
        public array $merchantUrls,
        public string $language = 'en',
        public array $buyerHistory = [],
        public array $orderHistory = [],
        public ?string $merchantCode = null,
        public ?string $token = null,
        public ?string $description = null,
        public array $meta = [],
        public ?array $attachment = null,
    ) {
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new ValidationException('The checkout currency must be a three-letter uppercase code.');
        }
        if (! in_array($language, ['en', 'ar'], true)) {
            throw new ValidationException('The checkout language must be en or ar.');
        }
        foreach (['success', 'cancel', 'failure'] as $name) {
            if (! isset($merchantUrls[$name]) || ! is_string($merchantUrls[$name])) {
                throw new ValidationException('Checkout merchant URLs must include success, cancel, and failure URLs.');
            }
            ValidatesInput::url($merchantUrls[$name], "merchant {$name} URL");
        }
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $payment = [
            'amount' => \YasserElgammal\TabbyPhp\Support\Decimal::positive($this->amount),
            'currency' => $this->currency,
            'buyer' => $this->buyer->toArray(),
            'shipping_address' => $this->shippingAddress->toArray(),
            'order' => $this->order->toArray(),
            'buyer_history' => (object) $this->buyerHistory,
            'order_history' => $this->orderHistory,
            'description' => $this->description,
            'meta' => $this->meta === [] ? null : $this->meta,
            'attachment' => $this->attachment,
        ];
        $payment = array_filter($payment, fn($value) => $value !== null);

        return array_filter([
            'payment' => $payment,
            'lang' => $this->language,
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => $this->merchantUrls,
            'token' => $this->token,
        ], fn($value) => $value !== null);
    }
}
