<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Client\Configuration;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class ConfigurationTest extends TestCase
{
    #[DataProvider('invalidConfigurationProvider')]
    public function test_invalid_configuration_is_rejected(array $arguments): void
    {
        $this->expectException(ValidationException::class);

        new Configuration(...$arguments);
    }

    public static function invalidConfigurationProvider(): array
    {
        return [
            'empty secret' => [['']],
            'invalid base URL' => [['secret', 'not-a-url']],
            'zero timeout' => [['secretKey' => 'secret', 'timeout' => 0]],
            'zero connect timeout' => [['secretKey' => 'secret', 'connectTimeout' => 0]],
            'negative retries' => [['secretKey' => 'secret', 'retryTimes' => -1]],
            'too many retries' => [['secretKey' => 'secret', 'retryTimes' => 11]],
            'negative retry sleep' => [['secretKey' => 'secret', 'retrySleep' => -1]],
            'excessive retry sleep' => [['secretKey' => 'secret', 'retrySleep' => 60_001]],
        ];
    }
}
