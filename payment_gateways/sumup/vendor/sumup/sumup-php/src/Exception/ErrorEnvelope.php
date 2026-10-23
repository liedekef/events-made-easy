<?php

namespace SumUp\Exception;

/**
 * Normalized representation of an unexpected API error payload.
 */
class ErrorEnvelope
{
    private int $status;

    private string $message;

    private mixed $raw;

    /**
     * @var array<string, array<int, string>>
     */
    private array $headers;

    /**
     * @param array<string, array<int, string>> $headers
     */
    public function __construct(int $status, string $message, mixed $raw = null, array $headers = [])
    {
        $this->status = $status;
        $this->message = $message;
        $this->raw = $raw;
        $this->headers = $headers;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRaw(): mixed
    {
        return $this->raw;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, int|string|array<string, array<int, string>>|mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'raw' => $this->raw,
            'headers' => $this->headers,
        ];
    }
}
