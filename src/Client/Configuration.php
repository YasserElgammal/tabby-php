<?php

namespace YasserElgammal\TabbyPhp\Client;

final readonly class Configuration
{
    public function __construct(
        public string $secretKey,
        public string $baseUrl = 'https://api.tabby.sa',
        public ?string $merchantCode = null,
        public int $timeout = 15,
        public int $connectTimeout = 5,
        public int $retryTimes = 2,
        public int $retrySleep = 200,
        public ?string $webhookSecret = null,
    ) {
    }
}
