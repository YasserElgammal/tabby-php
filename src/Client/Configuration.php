<?php

namespace YasserElgammal\TabbyPhp\Client;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

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
        if (trim($secretKey) === '') {
            throw new ValidationException('The Tabby secret key must not be empty.');
        }

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ! in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new ValidationException('The Tabby base URL must be a valid HTTP or HTTPS URL.');
        }

        if ($timeout <= 0 || $connectTimeout <= 0) {
            throw new ValidationException('Tabby timeouts must be greater than zero.');
        }

        if ($retryTimes < 0 || $retryTimes > 10 || $retrySleep < 0 || $retrySleep > 60_000) {
            throw new ValidationException('Tabby retry attempts must be between 0 and 10 and retry sleep between 0 and 60000 milliseconds.');
        }
    }
}
