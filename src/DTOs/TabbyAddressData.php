<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class TabbyAddressData
{
    public function __construct(public string $city, public string $address, public ?string $zip = null)
    {
        if (trim($city) === '' || trim($address) === '') {
            throw new \YasserElgammal\TabbyPhp\Exceptions\ValidationException('Shipping city and address are required.');
        }
    }
    /** @return array<string,string> */ public function toArray(): array
    {
        return array_filter([
            'city' => $this->city,
            'address' => $this->address,
            'zip' => $this->zip
        ], fn($v) => $v !== null);
    }
}
