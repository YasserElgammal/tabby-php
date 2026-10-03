<?php

namespace YasserElgammal\TabbyPhp\Support;

use DateTimeImmutable;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final class ValidatesInput
{
    public static function id(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 255 || ! preg_match('/^[A-Za-z0-9._:#-]+$/', $value)) {
            throw new ValidationException("The {$field} is invalid.");
        }
        return $value;
    }

    public static function url(string $value, string $field): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new ValidationException("The {$field} must be a valid HTTP or HTTPS URL.");
        }
        return $value;
    }

    public static function date(string $value, string $field): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (! $date || $date->format('Y-m-d') !== $value) {
            throw new ValidationException("The {$field} must use YYYY-MM-DD format.");
        }
        return $value;
    }

    public static function dateTime(string $value, string $field): string
    {
        if (DateTimeImmutable::createFromFormat(DATE_ATOM, $value) === false) {
            throw new ValidationException("The {$field} must be an ISO 8601 date-time.");
        }
        return $value;
    }
}
