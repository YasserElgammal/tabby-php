<?php

namespace YasserElgammal\TabbyPhp\Client\Http;

final readonly class Response
{
    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        if ($this->body === '') {
            return null;
        }

        return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
