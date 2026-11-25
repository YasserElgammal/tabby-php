<?php

namespace YasserElgammal\TabbyPhp\Client;

use JsonException;
use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;
use YasserElgammal\TabbyPhp\Client\Http\Response;
use YasserElgammal\TabbyPhp\Exceptions\ApiException;
use YasserElgammal\TabbyPhp\Exceptions\TransportException;

final class ApiClient
{
    private const MAX_RETRY_DELAY_MS = 2_000;

    public function __construct(
        private readonly Configuration $config,
        private readonly HttpClientInterface $http,
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
                $response = null;
                try {
                    $response = $this->http->request($method, $base . '/' . ltrim($path, '/'), $options);
                    $lastException = null;
                    if ($response->successful() || ! $this->shouldRetry($response->statusCode)) {
                        break;
                    }
                } catch (TransportException $e) {
                    $lastException = $e;
                }

                if ($i < $attempts - 1) {
                    $delay = $this->retryDelayMilliseconds($i, $response);
                    if ($delay > self::MAX_RETRY_DELAY_MS) {
                        break;
                    }
                    if ($delay > 0) {
                        usleep($delay * 1000);
                    }
                }
            }

            if ($response === null && $lastException !== null) {
                throw $lastException;
            }
            if ($response === null) {
                throw new TransportException('The Tabby API request completed without a response.');
            }
        } else {
            $response = $this->http->request($method, $base . '/' . ltrim($path, '/'), $options);
        }

        if (!$response->successful()) {
            $body = $this->decode($response, false) ?? ['body' => $response->body];
            $message = (string) ($body['error'] ?? $body['message'] ?? 'Tabby API request failed.');
            throw new ApiException($message, $response->statusCode, $body);
        }

        $decoded = $this->decode($response, true);
        if ($decoded === null && $response->body !== '') {
            throw new ApiException('Tabby returned an invalid JSON response.', $response->statusCode);
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, [408, 425, 429], true) || $statusCode >= 500;
    }

    private function retryDelayMilliseconds(int $attempt, ?Response $response): int
    {
        $retryAfter = $response === null ? null : $this->retryAfterMilliseconds($response);
        if ($retryAfter !== null) {
            return $retryAfter;
        }

        $base = min(self::MAX_RETRY_DELAY_MS, $this->config->retrySleep * (2 ** $attempt));
        if ($base === 0) {
            return 0;
        }

        return min(self::MAX_RETRY_DELAY_MS, $base + random_int(0, max(1, intdiv($base, 4))));
    }

    private function retryAfterMilliseconds(Response $response): ?int
    {
        foreach ($response->headers as $name => $values) {
            if (strcasecmp($name, 'Retry-After') !== 0 || $values === []) {
                continue;
            }

            $value = trim((string) $values[0]);
            if (ctype_digit($value)) {
                $seconds = (int) $value;
            } else {
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    return null;
                }
                $seconds = max(0, $timestamp - time());
            }

            return $seconds > intdiv(self::MAX_RETRY_DELAY_MS, 1000)
                ? self::MAX_RETRY_DELAY_MS + 1
                : $seconds * 1000;
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function decode(Response $response, bool $successful): ?array
    {
        try {
            return $response->json();
        } catch (JsonException $exception) {
            if ($successful) {
                throw new ApiException(
                    'Tabby returned an invalid JSON response.',
                    $response->statusCode,
                    ['body' => $response->body],
                    $exception,
                );
            }

            return ['body' => $response->body];
        }
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = [], bool $merchantHeader = false): array
    {
        return $this->request('GET', $path, ['query' => $query], $merchantHeader);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = [], bool $merchantHeader = false): array
    {
        return $this->request('POST', $path, ['json' => $payload], $merchantHeader);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function put(string $path, array $payload = [], bool $merchantHeader = false): array
    {
        return $this->request('PUT', $path, ['json' => $payload], $merchantHeader);
    }

    /** @return array<string, mixed> */
    public function delete(string $path, bool $merchantHeader = false): array
    {
        return $this->request('DELETE', $path, [], $merchantHeader);
    }
}
