<?php

namespace YasserElgammal\TabbyPhp\Resources;

use YasserElgammal\TabbyPhp\Client\ApiClient;
use YasserElgammal\TabbyPhp\DTOs\WebhookResponse;
use YasserElgammal\TabbyPhp\Exceptions\ValidationException;
use YasserElgammal\TabbyPhp\Support\ValidatesInput;

final class WebhookResource
{
    public function __construct(private readonly ApiClient $client) {}

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function create(string $url, bool $test = true, array $headers = []): array
    {
        $payload = ['url' => ValidatesInput::url($url, 'webhook URL')];
        if ($headers !== []) {
            if (! isset($headers['title'], $headers['value']) || trim($headers['title']) === '' || trim($headers['value']) === '') {
                throw new ValidationException('Webhook header requires non-empty title and value fields.');
            }
            $payload['header'] = ['title' => $headers['title'], 'value' => $headers['value']];
        }

        return $this->client->post('/api/v1/webhooks', $payload, true);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->client->get('/api/v1/webhooks', [], true);
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        return $this->client->get('/api/v1/webhooks/' . ValidatesInput::id($id, 'webhook ID'), [], true);
    }

    public function getResponse(string $id): WebhookResponse
    {
        return WebhookResponse::fromArray($this->get($id));
    }

    /** @return array<string, mixed> */
    public function update(string $id, string $url, bool $test = true): array
    {
        return $this->client->put('/api/v1/webhooks/' . ValidatesInput::id($id, 'webhook ID'), [
            'url' => ValidatesInput::url($url, 'webhook URL')
        ], true);
    }

    /** @return array<string, mixed> */
    public function delete(string $id): array
    {
        return $this->client->delete('/api/v1/webhooks/' . ValidatesInput::id($id, 'webhook ID'), true);
    }
}
