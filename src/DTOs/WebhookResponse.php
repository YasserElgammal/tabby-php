<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class WebhookResponse
{
    public function __construct(
        public ?string $id,
        public ?string $url,
        public array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (string) $data['id'] : null,
            isset($data['url']) ? (string) $data['url'] : null,
            $data,
        );
    }
}
