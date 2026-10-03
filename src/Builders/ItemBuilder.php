<?php

namespace YasserElgammal\TabbyPhp\Builders;

use YasserElgammal\TabbyPhp\DTOs\TabbyItemData;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class ItemBuilder
{
    private string $title = '';
    private int $quantity = 1;
    private string|int|float $unitPrice = 0;
    private string $referenceId = '';
    private bool $refundable = true;
    private string|int|float $discountAmount = '0.00';
    private ?string $description = null;
    private ?string $imageUrl = null;
    private ?string $productUrl = null;
    private ?string $gender = null;
    private ?string $category = null;
    private ?string $color = null;
    private ?string $productMaterial = null;
    private ?string $sizeType = null;
    private ?string $size = null;
    private ?string $brand = null;

    public function title(string $v): self
    {
        $this->title = $v;
        return $this;
    }

    public function quantity(int $v): self
    {
        $this->quantity = $v;
        return $this;
    }

    public function unitPrice(string|int|float $v): self
    {
        $this->unitPrice = $v;
        return $this;
    }
    
    public function referenceId(string $v): self
    {
        $this->referenceId = $v;
        return $this;
    }
    public function refundable(bool $v = true): self
    {
        $this->refundable = $v;
        return $this;
    }

    public function description(?string $v): self
    {
        $this->description = $v;
        return $this;
    }
    public function discountAmount(string|int|float $v): self
    {
        $this->discountAmount = $v;
        return $this;
    }
    public function imageUrl(?string $v): self
    {
        $this->imageUrl = $v;
        return $this;
    }
    public function productUrl(?string $v): self
    {
        $this->productUrl = $v;
        return $this;
    }
    public function gender(?string $v): self
    {
        $this->gender = $v;
        return $this;
    }
    public function category(?string $v): self
    {
        $this->category = $v;
        return $this;
    }
    public function color(?string $v): self
    {
        $this->color = $v;
        return $this;
    }
    public function productMaterial(?string $v): self
    {
        $this->productMaterial = $v;
        return $this;
    }
    public function sizeType(?string $v): self
    {
        $this->sizeType = $v;
        return $this;
    }
    public function size(?string $v): self
    {
        $this->size = $v;
        return $this;
    }
    public function brand(?string $v): self
    {
        $this->brand = $v;
        return $this;
    }


    public function build(): TabbyItemData
    {
        if (trim($this->title) === '' || $this->quantity < 1) throw new ValidationException('A Tabby item needs a title and positive quantity.');
        \YasserElgammal\TabbyPhp\Support\ValidatesInput::id($this->referenceId, 'item reference ID');
        \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->unitPrice, 'item unit price');
        \YasserElgammal\TabbyPhp\Support\Decimal::nonNegative($this->discountAmount, 'item discount amount');
        if ($this->imageUrl !== null) \YasserElgammal\TabbyPhp\Support\ValidatesInput::url($this->imageUrl, 'item image URL');
        if ($this->productUrl !== null) \YasserElgammal\TabbyPhp\Support\ValidatesInput::url($this->productUrl, 'item product URL');
        return new TabbyItemData($this->title, $this->quantity, $this->unitPrice, $this->referenceId, $this->refundable, $this->description, $this->discountAmount, $this->imageUrl, $this->productUrl, $this->gender, $this->category, $this->color, $this->productMaterial, $this->sizeType, $this->size, $this->brand);
    }
}
