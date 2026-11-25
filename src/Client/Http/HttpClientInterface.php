<?php

namespace YasserElgammal\TabbyPhp\Client\Http;

use YasserElgammal\TabbyPhp\Exceptions\TransportException;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $options
     * @throws TransportException On a transport failure.
     */
    public function request(string $method, string $uri, array $options = []): Response;
}
