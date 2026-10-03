<?php

namespace YasserElgammal\TabbyPhp\Client\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use YasserElgammal\TabbyPhp\Exceptions\TransportException;

final class GuzzleHttpClient implements HttpClientInterface
{
    private GuzzleClient $client;

    public function __construct(?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient();
    }

    public function request(string $method, string $uri, array $options = []): Response
    {
        try {
            $response = $this->client->request($method, $uri, $options);

            return new Response(
                $response->getStatusCode(),
                $response->getHeaders(),
                (string) $response->getBody()
            );
        } catch (ConnectException $e) {
            throw new TransportException('Unable to connect to the Tabby API.', 0, $e);
        } catch (GuzzleException $e) {
            // Re-wrap the exception or extract the response if available
            if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                $response = $e->getResponse();
                return new Response(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            throw new TransportException('An error occurred while communicating with the Tabby API.', 0, $e);
        }
    }
}
