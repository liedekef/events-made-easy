<?php

namespace SumUp\HttpClient;

/**
 * Typed request options for HTTP calls.
 */
class RequestOptions
{
    /**
     * Additional headers to apply on top of the SDK defaults.
     *
     * @var array<string, string>
     */
    public array $headers = [];

    /**
     * Total request timeout in seconds.
     *
     * @var int|null
     */
    public ?int $timeout = null;

    /**
     * Connection timeout in seconds.
     *
     * @var int|null
     */
    public ?int $connectTimeout = null;

    /**
     * Number of retry attempts for transient failures.
     *
     * @var int|null
     */
    public ?int $retries = null;

    /**
     * Base retry backoff in milliseconds.
     *
     * @var int|null
     */
    public ?int $retryBackoffMs = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        ?int $timeout = null,
        ?int $connectTimeout = null,
        ?int $retries = null,
        ?int $retryBackoffMs = null,
        array $headers = []
    ) {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->retries = $retries;
        $this->retryBackoffMs = $retryBackoffMs;
        $this->headers = $headers;
    }
}
