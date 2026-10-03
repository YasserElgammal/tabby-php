<?php

namespace YasserElgammal\TabbyPhp\Support;

use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class Decimal
{
    /** @param list<string|int|float> $subtrahends */
    public static function subtract(string|int|float $value, array $subtrahends, string $field = 'amount'): string
    {
        $values = array_map(fn ($item) => self::nonNegative($item, $field), [$value, ...$subtrahends]);
        $scale = max(array_map(fn (string $item) => strlen(explode('.', $item, 2)[1] ?? ''), $values));
        $scale = max(2, $scale);
        $factor = 10 ** $scale;
        $units = self::units(array_shift($values), $scale);
        foreach ($values as $item) {
            $units -= self::units($item, $scale);
        }
        if ($units <= 0) {
            throw new ValidationException("The {$field} must be greater than zero.");
        }

        return number_format($units / $factor, $scale, '.', '');
    }

    public static function positive(string|int|float $value, string $field = 'amount'): string
    {
        $decimal = self::normalize($value, $field);

        if ((float) $decimal <= 0) {
            throw new ValidationException("The {$field} must be greater than zero.");
        }

        return $decimal;
    }

    public static function nonNegative(string|int|float $value, string $field): string
    {
        $decimal = self::normalize($value, $field);

        if ((float) $decimal < 0) {
            throw new ValidationException("The {$field} must not be negative.");
        }

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

        return ($whole === '' ? '0' : $whole).'.'.str_pad($fraction, 2, '0');
    }

    private static function units(string $value, int $scale): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * (10 ** $scale)) + (int) str_pad($fraction, $scale, '0');
    }
}
