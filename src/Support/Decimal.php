<?php

namespace YasserElgammal\TabbyPhp\Support;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class Decimal
{
    /** @param list<string|int|float> $subtrahends */
    public static function subtract(string|int|float $value, array $subtrahends, string $field = 'amount'): string
    {
        $values = array_map(fn($item) => self::nonNegative($item, $field), [$value, ...$subtrahends]);
        $scale = max(array_map(fn(string $item) => strlen(explode('.', $item, 2)[1] ?? ''), $values));
        $scale = max(2, $scale);
        $units = self::units(array_shift($values), $scale);
        foreach ($values as $item) {
            $subtrahend = self::units($item, $scale);
            if (self::compare($units, $subtrahend) <= 0) {
                throw new ValidationException("The {$field} must be greater than zero.");
            }
            $units = self::subtractUnits($units, $subtrahend);
        }
        if ($units === '0') {
            throw new ValidationException("The {$field} must be greater than zero.");
        }

        $units = str_pad($units, $scale + 1, '0', STR_PAD_LEFT);
        return substr($units, 0, -$scale) . '.' . substr($units, -$scale);
    }

    public static function positive(string|int|float $value, string $field = 'amount'): string
    {
        $decimal = self::normalize($value, $field);

        if (preg_match('/^0\.0+$/', $decimal) === 1) {
            throw new ValidationException("The {$field} must be greater than zero.");
        }

        return $decimal;
    }

    public static function nonNegative(string|int|float $value, string $field): string
    {
        $decimal = self::normalize($value, $field);

        return $decimal;
    }

    private static function normalize(string|int|float $value, string $field): string
    {
        $raw = is_string($value) ? trim($value) : (string) $value;

        if ($raw === '' || ! preg_match('/^\d+(?:\.\d{1,3})?$/', $raw)) {
            throw new ValidationException("The {$field} must be a decimal value with at most three fractional digits.");
        }

        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '');
        $whole = ltrim($whole, '0');

        return ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0');
    }

    private static function units(string $value, int $scale): string
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $units = ltrim($whole . str_pad($fraction, $scale, '0'), '0');
        return $units === '' ? '0' : $units;
    }

    private static function compare(string $left, string $right): int
    {
        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private static function subtractUnits(string $left, string $right): string
    {
        $result = '';
        $borrow = 0;
        $right = str_pad($right, strlen($left), '0', STR_PAD_LEFT);

        for ($i = strlen($left) - 1; $i >= 0; $i--) {
            $digit = ((int) $left[$i]) - ((int) $right[$i]) - $borrow;
            if ($digit < 0) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result = $digit . $result;
        }

        $result = ltrim($result, '0');
        return $result === '' ? '0' : $result;
    }
}
