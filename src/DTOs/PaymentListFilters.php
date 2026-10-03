<?php

namespace YasserElgammal\TabbyPhp\DTOs;

use DateTimeImmutable;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;

final readonly class PaymentListFilters
{
    public function __construct(
        public ?string $createdAtGte = null,
        public ?string $createdAtLte = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
        foreach (['createdAtGte' => $createdAtGte, 'createdAtLte' => $createdAtLte] as $field => $value) {
            if ($value === null) {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            if ($date === false || $date->format('Y-m-d') !== $value) {
                throw new ValidationException("The {$field} filter must use YYYY-MM-DD format.");
            }
        }
        if ($limit < 1 || $limit > 100 || $offset < 0) {
            throw new ValidationException('Payment list limit must be between 1 and 100 and offset must not be negative.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'created_at__gte' => $this->createdAtGte,
            'created_at__lte' => $this->createdAtLte,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ], fn ($value) => $value !== null);
    }
}
