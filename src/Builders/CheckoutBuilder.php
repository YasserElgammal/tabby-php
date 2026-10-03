<?php

namespace YasserElgammal\TabbyPhp\Builders;

use DateTimeInterface;
use YasserElgammal\TabbyPhp\DTOs\{TabbyAddressData, TabbyBuyerData, TabbyCheckoutData, TabbyItemData, TabbyOrderData};
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class CheckoutBuilder
{
    private string|int|float $amount = 0;
    private string|int|float $tax = 0;
    private string|int|float $shipping = 0;
    private string|int|float $discount = 0;
    private string $currency = 'SAR';
    private string $referenceId = '';
    private string $language = 'en';
    private ?TabbyBuyerData $buyer = null;
    private ?TabbyAddressData $address = null;
    private array $items = [];
    private array $urls = [];
    private array $buyerHistory = [];
    private array $orderHistory = [];
    private ?string $merchantCode = null;
    private ?string $token = null;
    private ?string $updatedAt = null;
    private ?string $description = null;
    private array $meta = [];
    private ?array $attachment = null;

    public function amount(string|int|float $v): self
    {
        $this->amount = $v;
        return $this;
    }

    public function currency(string $v): self
    {
        $this->currency = $v;
        return $this;
    }
    
    public function referenceId(string $v): self
    {
        $this->referenceId = $v;
        return $this;
    }

    public function language(string $v): self
    {
        $this->language = $v;
        return $this;
    }

    public function buyer(TabbyBuyerData $v): self
    {
        $this->buyer = $v;
        return $this;
    }

    public function shippingAddress(TabbyAddressData $v): self
    {
        $this->address = $v;
        return $this;
    }

    public function item(TabbyItemData $v): self
    {
        $this->items[] = $v;
        return $this;
    }

    public function items(iterable $v): self
    {
        foreach ($v as $i) $this->item($i);
        return $this;
    }

    public function tax(string|int|float $v): self
    {
        $this->tax = $v;
        return $this;
    }

    public function shipping(string|int|float $v): self
    {
        $this->shipping = $v;
        return $this;
    }

    public function discount(string|int|float $v): self
    {
        $this->discount = $v;
        return $this;
    }

    public function updatedAt(DateTimeInterface|string $v): self
    {
        $this->updatedAt = $v instanceof DateTimeInterface ? $v->format(DATE_ATOM) : $v;
        return $this;
    }

    public function merchantUrls(string $success, string $cancel, string $failure): self
    {
        $this->urls = compact('success', 'cancel', 'failure');
        return $this;
    }

    public function buyerHistory(array $v): self
    {
        $this->buyerHistory = $v;
        return $this;
    }

    public function orderHistory(array $v): self
    {
        $this->orderHistory = $v;
        return $this;
    }

    public function merchantCode(?string $v): self
    {
        $this->merchantCode = $v;
        return $this;
    }

    public function token(?string $v): self
    {
        $this->token = $v;
        return $this;
    }

    public function description(?string $v): self
    {
        $this->description = $v;
        return $this;
    }
    public function meta(array $v): self
    {
        $this->meta = $v;
        return $this;
    }
    public function attachment(string $body, string $contentType = 'application/vnd.tabby.v1+json'): self
    {
        $this->attachment = ['body' => $body, 'content_type' => $contentType];
        return $this;
    }

    public function build(): TabbyCheckoutData
    {
        \YasserElgammal\TabbyPhp\Support\Decimal::positive($this->amount);
        \YasserElgammal\TabbyPhp\Support\ValidatesInput::id($this->referenceId, 'order reference ID');
        if (! $this->buyer || ! $this->address || $this->items === [] || count($this->urls) !== 3) throw new ValidationException('Tabby checkout requires buyer, address, items, and merchant URLs.');
        foreach ($this->urls as $name => $url) \YasserElgammal\TabbyPhp\Support\ValidatesInput::url($url, "merchant {$name} URL");
        if ($this->updatedAt !== null) \YasserElgammal\TabbyPhp\Support\ValidatesInput::dateTime($this->updatedAt, 'order updated at');
        $order = new TabbyOrderData($this->referenceId, $this->tax, $this->shipping, $this->discount, $this->updatedAt ?? date(DATE_ATOM), $this->items);
        return new TabbyCheckoutData($this->amount, $this->currency, $this->buyer, $this->address, $order, $this->urls, $this->language, $this->buyerHistory, $this->orderHistory, $this->merchantCode, $this->token, $this->description, $this->meta, $this->attachment);
    }
}
