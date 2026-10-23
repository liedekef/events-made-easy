<?php

namespace SumUp\Exception;

/**
 * Class SDKException
 *
 * @package SumUp\Exception
 */
class SDKException extends \Exception
{
    /**
     * HTTP status code returned by the API, if any.
     *
     * @var int
     */
    protected int $statusCode;

    /**
     * Parsed response body or raw string when the response is not JSON.
     *
     * @var mixed
     */
    protected mixed $responseBody;

    /**
     * @param string $message
     * @param int $statusCode
     * @param mixed $responseBody
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        mixed $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Returns the HTTP status code provided by the API, or 0 when absent.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the decoded response body or the raw string payload.
     *
     * @return mixed
     */
    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }
}
