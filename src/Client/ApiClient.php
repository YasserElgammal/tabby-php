<?php

namespace YasserElgammal\TabbyPhp\Client;

use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;

final class ApiClient
{
    public function __construct(
        private readonly Configuration $config,
        private readonly HttpClientInterface $http
    ) {}

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $options = [], bool $merchantHeader = false): array
    {
        $base = rtrim($this->config->baseUrl, '/');
        $base = preg_replace('#/api(?:/v\d+)?$#', '', $base) ?: $base;

        $headers = [
            'Authorization' => 'Bearer ' . $this->config->secretKey,
            'Accept' => 'application/json',
        ];

        if ($merchantHeader) {
            if (trim((string) $this->config->merchantCode) === '') {
                throw new ApiException('The Tabby merchant code is not configured.', 0);
            }
            $headers['X-Merchant-Code'] = $this->config->merchantCode;
        }

        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
            unset($options['headers']);
        }

        $options['headers'] = $headers;
        $options['connect_timeout'] = $this->config->connectTimeout;
        $options['timeout'] = $this->config->timeout;
        // Keep compat with Guzzle format where appropriate or HTTP client implementation
        if (isset($options['json'])) {
            $headers['Content-Type'] = 'application/json';
            $options['headers'] = $headers;
        }

        if (strtoupper($method) === 'GET' && $this->config->retryTimes > 0) {
            $attempts = $this->config->retryTimes + 1;
            $response = null;
            $lastException = null;

            for ($i = 0; $i < $attempts; $i++) {
                try {
                    $response = $this->http->request($method, $base . '/' . ltrim($path, '/'), $options);
                    if ($response->successful()) {
                        break;
                    }
                } catch (\Exception $e) {
                    $lastException = $e;
                }
                
                if ($i < $attempts - 1) {
                    usleep($this->config->retrySleep * 1000);
                }
            }

            if ($response === null && $lastException !== null) {
                throw $lastException;
            }
        } else {
            $response = $this->http->request($method, $base . '/' . ltrim($path, '/'), $options);
        }

        if (!$response->successful()) {
            $body = $response->json() ?? ['body' => $response->body];
            $message = (string) ($body['error'] ?? $body['message'] ?? 'Tabby API request failed.');
            throw new ApiException($message, $response->statusCode, $body);
        }

        $decoded = $response->json();
        if ($decoded === null && $response->body !== '') {
            throw new ApiException('Tabby returned an invalid JSON response.', $response->statusCode);
        }

        return is_array($decoded) ? $decoded : [];
    }

    public function get(string $path, array $query = [], bool $merchantHeader = false): array
    {
        return $this->request('GET', $path, ['query' => $query], $merchantHeader);
    }

    public function post(string $path, array $payload = [], bool $merchantHeader = false): array
    {
        return $this->request('POST', $path, ['json' => $payload], $merchantHeader);
    }

    public function put(string $path, array $payload = [], bool $merchantHeader = false): array
    {
        return $this->request('PUT', $path, ['json' => $payload], $merchantHeader);
    }

    public function delete(string $path, bool $merchantHeader = false): array
    {
        return $this->request('DELETE', $path, [], $merchantHeader);
    }
}
