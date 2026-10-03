<?php

namespace YasserElgammal\TabbyPhp\Security;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class WebhookVerifier
{
    public function __construct(private readonly string $secret)
    {
        if (trim($secret) === '') {
            throw new ValidationException('The Tabby webhook secret is not configured.');
        }
    }

    public function verify(?string $providedSecret): bool
    {
        return is_string($providedSecret) && hash_equals($this->secret, $providedSecret);
    }
}
