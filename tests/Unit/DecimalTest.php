<?php

namespace YasserElgammal\TabbyPhp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YasserElgammal\TabbyPhp\Support\Decimal;

final class DecimalTest extends TestCase
{
    public function test_subtraction_keeps_precision_for_values_larger_than_php_integers(): void
    {
        self::assertSame(
            '999999999999999999999999998.999',
            Decimal::subtract('1000000000000000000000000000.000', ['1.001']),
        );
    }

    public function test_positive_handles_tiny_valid_decimal_without_float_conversion(): void
    {
        self::assertSame('0.001', Decimal::positive('0.001'));
    }
}
