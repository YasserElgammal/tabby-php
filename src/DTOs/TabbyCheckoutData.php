<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class TabbyCheckoutData
{
    /** @param array<string,mixed> $buyerHistory @param list<array<string,mixed>> $orderHistory @param array{success:string,cancel:string,failure:string} $merchantUrls */
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
    ) {}
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $payment = [
            'amount' => \YasserElgammal\TabbyPhp\Support\Decimal::positive($this->amount),
            'currency' => $this->currency,
            'buyer' => $this->buyer->toArray(),
            'shipping_address' => $this->shippingAddress->toArray(),
            'order' => $this->order->toArray(),
            'buyer_history' => (object)$this->buyerHistory,
            'order_history' => $this->orderHistory,
            'description' => $this->description,
            'meta' => $this->meta === [] ? null : $this->meta,
            'attachment' => $this->attachment,
        ];
        $payment = array_filter($payment, fn ($value) => $value !== null);

        return array_filter([
            'payment' => $payment,
            'lang' => $this->language,
            'merchant_code' => $this->merchantCode,
            'merchant_urls' => $this->merchantUrls,
            'token' => $this->token,
        ], fn ($value) => $value !== null);
    }
}
