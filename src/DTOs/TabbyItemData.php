<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class TabbyItemData
{
    public function __construct(
        public string $title,
        public int $quantity,
        public string|int|float $unitPrice,
        public string $referenceId,
        public bool $isRefundable = true,
        public ?string $description = null,
        public string|int|float $discountAmount = '0.00',
        public ?string $imageUrl = null,
        public ?string $productUrl = null,
        public ?string $gender = null,
        public ?string $category = null,
        public ?string $color = null,
        public ?string $productMaterial = null,
        public ?string $sizeType = null,
        public ?string $size = null,
        public ?string $brand = null,
    ) {}
    
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'quantity' => $this->quantity,
            'unit_price' => \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->unitPrice, 'item unit price'),
            'discount_amount' => \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->discountAmount, 'item discount amount'),
            'reference_id' => $this->referenceId,
            'is_refundable' => $this->isRefundable,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'product_url' => $this->productUrl,
            'gender' => $this->gender,
            'category' => $this->category,
            'color' => $this->color,
            'product_material' => $this->productMaterial,
            'size_type' => $this->sizeType,
            'size' => $this->size,
            'brand' => $this->brand,
        ], fn ($value) => $value !== null);
    }
}
