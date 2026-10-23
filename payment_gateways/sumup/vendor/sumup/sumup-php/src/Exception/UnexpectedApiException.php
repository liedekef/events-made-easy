<?php

namespace SumUp\Exception;

/**
 * Represents an API error response that does not match the OpenAPI-described error schema.
 */
class UnexpectedApiException extends ApiException
{
    private ErrorEnvelope $errorEnvelope;

    private ?string $rawResponseBody;

    /**
     * @param array<string, mixed>|null $headers
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        mixed $responseBody = null,
        ?string $httpMethod = null,
        ?string $path = null,
        ?array $headers = null,
        ?string $rawResponseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $responseBody, $httpMethod, $path, $previous);
        $this->rawResponseBody = $rawResponseBody;
        $this->errorEnvelope = new ErrorEnvelope(
            $statusCode,
            $message,
            $rawResponseBody ?? $responseBody,
            self::normalizeHeaders($headers)
        );
    }

    public function getErrorEnvelope(): ErrorEnvelope
    {
        return $this->errorEnvelope;
    }

    public function getRawResponseBody(): ?string
    {
        return $this->rawResponseBody;
    }

    /**
     * @param array<string, mixed>|null $headers
     *
     * @return array<string, array<int, string>>
     */
    private static function normalizeHeaders(?array $headers): array
    {
        if ($headers === null) {
            return [];
        }

        $normalized = [];
        foreach ($headers as $name => $value) {
            if ($name == '') {
                continue;
            }

            if (is_array($value)) {
                $items = [];
                foreach ($value as $item) {
                    if (is_scalar($item) || (is_object($item) && method_exists($item, '__toString'))) {
                        $items[] = (string) $item;
                    }
                }
                if (!empty($items)) {
                    $normalized[$name] = $items;
                }
                continue;
            }

            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $normalized[$name] = [(string) $value];
            }
        }

        return $normalized;
    }
}
