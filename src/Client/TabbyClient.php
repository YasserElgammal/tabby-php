<?php

namespace YasserElgammal\TabbyPhp\Client;

use YasserElgammal\TabbyPhp\Builders\CheckoutBuilder;
use YasserElgammal\TabbyPhp\Builders\ItemBuilder;
use YasserElgammal\TabbyPhp\Resources\CheckoutResource;
use YasserElgammal\TabbyPhp\Resources\PaymentResource;
use YasserElgammal\TabbyPhp\Resources\WebhookResource;
use YasserElgammal\TabbyPhp\Security\WebhookVerifier;
use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;
use YasserElgammal\TabbyPhp\Client\Http\GuzzleHttpClient;

final class TabbyClient
{
    private readonly ApiClient $apiClient;

    public function __construct(
        private readonly Configuration $config,
        ?HttpClientInterface $httpClient = null
    ) {
        $this->apiClient = new ApiClient($config, $httpClient ?? new GuzzleHttpClient());
    }

    public function checkout(): CheckoutBuilder
    {
        return new CheckoutBuilder();
    }

    public function item(): ItemBuilder
    {
        return new ItemBuilder();
    }

    public function checkouts(): CheckoutResource
    {
        return new CheckoutResource($this->apiClient);
    }

    public function payments(): PaymentResource
    {
        return new PaymentResource($this->apiClient);
    }

    public function webhooks(): WebhookResource
    {
        return new WebhookResource($this->apiClient);
    }

    public function webhookVerifier(): WebhookVerifier
    {
        return new WebhookVerifier((string) $this->config->webhookSecret);
    }
}
