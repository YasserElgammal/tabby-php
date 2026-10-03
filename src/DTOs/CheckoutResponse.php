<?php

namespace YasserElgammal\TabbyPhp\DTOs;


final readonly class CheckoutResponse
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public ?string $paymentId,
        public ?string $status,
        public ?string $rejectionReasonCode,
        public array $raw,
        public ?string $sessionId = null,
    ) {}

    public function url(): ?string
    {
        $url = $this->raw['configuration']['available_products']['installments'][0]['web_url'] ?? null;
        return is_string($url) ? $url : null;
    }

    public function isAvailable(): bool
    {
        return $this->status !== 'rejected' && $this->url() !== null;
    }

    /** @return array<string,mixed> */ public function toArray(): array
    {
        return $this->raw;
    }
}
