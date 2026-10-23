<?php

namespace SumUp\Exception;

/**
 * Represents an API error decoded from a non-2xx response.
 */
class ApiException extends SDKException
{
    /**
     * @var string|null
     */
    protected ?string $httpMethod;

    /**
     * @var string|null
     */
    protected ?string $path;

    public function __construct(
        string $message = '',
        int $statusCode = 0,
        mixed $responseBody = null,
        ?string $httpMethod = null,
        ?string $path = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
        $this->httpMethod = $httpMethod;
        $this->path = $path;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
}
