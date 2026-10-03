<?php

namespace YasserElgammal\TabbyPhp\Client\Http;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $uri, array $options = []): Response;
}
