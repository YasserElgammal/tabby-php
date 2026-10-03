<?php

namespace YasserElgammal\TabbyPhp\Tests\Support;

use RuntimeException;
use Throwable;
use YasserElgammal\TabbyPhp\Client\Http\HttpClientInterface;
use YasserElgammal\TabbyPhp\Client\Http\Response;

final class FakeHttpClient implements HttpClientInterface
{
    /** @var list<array{method: string, uri: string, options: array<string, mixed>}> */
    public array $requests = [];

    /** @var list<Response|Throwable> */
    private array $queue;

    public function __construct(Response|Throwable ...$queue)
    {
        $this->queue = $queue;
    }

    public function request(string $method, string $uri, array $options = []): Response
    {
        $this->requests[] = compact('method', 'uri', 'options');

        $result = array_shift($this->queue);

        if ($result instanceof Throwable) {
            throw $result;
        }

        if (! $result instanceof Response) {
            throw new RuntimeException('No fake HTTP response was queued.');
        }

        return $result;
    }
}
