<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class TabbyOrderData
{
    /** @param list<TabbyItemData> $items */
    public function __construct(
        public string $referenceId,
        public string|int|float $taxAmount,
        public string|int|float $shippingAmount,
        public string|int|float $discountAmount,
        public string $updatedAt,
        public array $items
    ) {}
    /** @return array<string,mixed> */ public function toArray(): array
    {
        return [
            'reference_id' => $this->referenceId,
            'tax_amount' => \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->taxAmount, 'tax amount'),
            'shipping_amount' => \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->shippingAmount, 'shipping amount'),
            'discount_amount' => \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->discountAmount, 'discount amount'),
            'updated_at' => $this->updatedAt,
            'items' => array_map(fn(TabbyItemData $i) => $i->toArray(), $this->items)
        ];
    }
}
