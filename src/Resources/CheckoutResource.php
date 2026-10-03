<?php

namespace YasserElgammal\TabbyPhp\Resources;

use YasserElgammal\TabbyPhp\Client\ApiClient;
use YasserElgammal\TabbyPhp\DTOs\CheckoutResponse;
use YasserElgammal\TabbyPhp\DTOs\TabbyCheckoutData;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;

final class CheckoutResource
{
    public function __construct(private readonly ApiClient $client) {}

    public function create(TabbyCheckoutData $checkout): CheckoutResponse
    {
        return $this->createFromArray($checkout->toArray());
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createFromArray(array $payload): CheckoutResponse
    {
        $data = $this->client->post('/api/v2/checkout', $payload);

        if (! isset($data['id'], $data['payment']['id'])) {
            throw new ApiException('Tabby returned an invalid checkout response.', 200, $data);
        }

        return new CheckoutResponse(
            isset($data['payment']['id']) ? (string) $data['payment']['id'] : null,
            isset($data['status']) ? (string) $data['status'] : null,
            isset($data['rejection_reason_code']) ? (string) $data['rejection_reason_code'] : null,
            $data,
            (string) $data['id']
        );
    }
}
