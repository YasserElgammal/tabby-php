<?php

namespace YasserElgammal\TabbyPhp\DTOs;

use YasserElgammal\TabbyPhp\Support\ValidatesInput;

final readonly class PaymentUpdateData
{
    public function __construct(public string $referenceId)
    {
        ValidatesInput::id($referenceId, 'order reference ID');
    }

    /** @return array{order: array{reference_id: string}} */
    public function toArray(): array
    {
        return ['order' => ['reference_id' => $this->referenceId]];
    }
}
