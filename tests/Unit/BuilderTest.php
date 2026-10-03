<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Client\TabbyClient;
use PHPUnit\Framework\TestCase;

final class BuilderTest extends TestCase
{
    public function test_invalid_item_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $config = new \YasserElgammal\TabbyPhp\Client\Configuration('secret');
        $client = new TabbyClient($config);
        $client->item()->build();
    }
}
