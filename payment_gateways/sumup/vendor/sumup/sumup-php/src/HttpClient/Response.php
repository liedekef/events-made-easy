<?php

namespace SumUp\HttpClient;

/**
 * Class Response
 *
 * @package SumUp\HttpClient
 */
class Response
{
    /**
     * The HTTP response code.
     *
     * @var int
     */
    protected int $httpResponseCode;

    /**
     * The response body.
     *
     * @var mixed
     */
    protected mixed $body;

    /**
     * Normalized response headers.
     *
     * @var array<string, array<int, string>>
     */
    protected array $headers;

    /**
     * Raw response body before parsing, when available.
     *
     * @var string|null
     */
    protected ?string $rawBody;

    /**
     * Response constructor.
     *
     * @param int $httpResponseCode
     * @param mixed $body
     * @param array<string, array<int, string>> $headers
     * @param string|null $rawBody
     *
     */
    public function __construct(
        int $httpResponseCode,
        mixed $body,
        array $headers = [],
        ?string $rawBody = null
    ) {
        $this->httpResponseCode = $httpResponseCode;
        $this->body = $body;
        $this->headers = $headers;
        $this->rawBody = $rawBody;
    }

    /**
     * Get HTTP response code.
     *
     * @return int
     */
    public function getHttpResponseCode(): int
    {
        return $this->httpResponseCode;
    }

    /**
     * Get the response body.
     *
     * @return array|mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Get normalized response headers.
     *
     * @return array<string, array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get raw response body before JSON decoding, when available.
     *
     * @return string|null
     */
    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

}
