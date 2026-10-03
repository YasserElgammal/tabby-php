<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class PaymentResponse
{
    public function __construct(
        public ?string $id,
        public ?string $status,
        public ?string $amount,
        public array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (string) $data['id'] : null,
            isset($data['status']) ? (string) $data['status'] : null,
            isset($data['amount']) ? (string) $data['amount'] : null,
            $data,
        );
    }
}
