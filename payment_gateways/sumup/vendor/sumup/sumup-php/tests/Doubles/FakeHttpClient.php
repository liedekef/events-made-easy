<?php

namespace SumUp\Tests\Doubles;

use RuntimeException;
use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestOptions;
use SumUp\HttpClient\Response;

/**
 * Lightweight test double that records outgoing requests and can optionally
 * fail when an HTTP call is not expected.
 */
class FakeHttpClient implements HttpClientInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private $requests = [];

    /**
     * @var Response
     */
    private $response;

    /**
     * @var bool
     */
    private $failOnCall;

    public function __construct(Response $response, bool $failOnCall = false)
    {
        $this->response = $response;
        $this->failOnCall = $failOnCall;
    }

    /**
     * {@inheritdoc}
     */
    public function send(string $method, string $url, array $body, array $headers, ?RequestOptions $options = null): Response
    {
        if ($this->failOnCall) {
            throw new RuntimeException('HTTP client should not have been called.');
        }

        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'body' => $body,
            'headers' => $headers,
        ];

        return $this->response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRequests()
    {
        return $this->requests;
    }
}
