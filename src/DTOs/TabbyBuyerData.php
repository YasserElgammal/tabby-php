<?php

namespace YasserElgammal\TabbyPhp\DTOs;

final readonly class TabbyBuyerData
{
    public function __construct(public string $phone, public string $email, public string $name, public ?string $dob = null)
    {
        if (trim($phone) === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || trim($name) === '') {
            throw new \YasserElgammal\TabbyPhp\Exceptions\ValidationException('Buyer phone, valid email, and name are required.');
        }
        if ($dob !== null) {
            \YasserElgammal\TabbyPhp\Support\ValidatesInput::date($dob, 'buyer date of birth');
        }
    }
    /** @return array<string,string> */ public function toArray(): array
    {
        return array_filter([
            'phone' => $this->phone,
            'email' => $this->email,
            'name' => $this->name,
            'dob' => $this->dob,
        ], fn ($value) => $value !== null);
    }
}
