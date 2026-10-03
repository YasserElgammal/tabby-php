<?php

namespace YasserElgammal\TabbyPhp\Exceptions;

class ApiException extends TabbyException
{
    /**
     * @param array<string, mixed> $response
     */
    public function __construct(string $message, int $status, public readonly array $response = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
