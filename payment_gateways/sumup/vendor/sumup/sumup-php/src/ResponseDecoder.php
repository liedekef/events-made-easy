<?php

namespace SumUp;

use SumUp\Exception\ApiException;
use SumUp\Exception\UnexpectedApiException;
use SumUp\HttpClient\Response;

/**
 * Converts HTTP responses into SDK models or scalar values.
 */
class ResponseDecoder
{
    /**
     * Decode a response and throw API exceptions for non-2xx statuses.
     *
     * @param Response $response
     * @param array<int|string, mixed>|string|null $successDescriptors
     * @param array<int|string, mixed>|string|null $errorDescriptors
     * @param string|null $httpMethod
     * @param string|null $path
     *
     * @return mixed
     *
     * @throws ApiException
     * @throws UnexpectedApiException
     */
    public static function decodeOrThrow(
        Response $response,
        $successDescriptors = null,
        $errorDescriptors = null,
        ?string $httpMethod = null,
        ?string $path = null
    ) {
        $statusCode = $response->getHttpResponseCode();
        if ($statusCode >= 200 && $statusCode < 300) {
            return self::decode($response, $successDescriptors);
        }

        if (self::hasDescriptorForStatus($errorDescriptors, $statusCode)) {
            $decodedErrorBody = self::decode($response, $errorDescriptors);
            $message = self::extractErrorMessage($decodedErrorBody, self::defaultErrorMessage($statusCode));

            throw new ApiException(
                $message,
                $statusCode,
                $decodedErrorBody,
                $httpMethod,
                $path
            );
        }

        $rawErrorBody = $response->getBody();
        $message = self::extractErrorMessage($rawErrorBody, self::defaultUnexpectedErrorMessage($statusCode));

        throw new UnexpectedApiException(
            $message,
            $statusCode,
            $rawErrorBody,
            $httpMethod,
            $path,
            $response->getHeaders(),
            $response->getRawBody()
        );
    }

    /**
     * Decode a response using the provided descriptor map or class name.
     *
     * @param Response $response
     * @param array<int|string, mixed>|string|null $descriptors Can be a descriptor array, a class name string, or null
     *
     * @return mixed
     */
    public static function decode(Response $response, $descriptors = null)
    {
        // If a simple class name string is provided, use it directly
        if (is_string($descriptors)) {
            return Hydrator::hydrate($response->getBody(), $descriptors);
        }

        // If null or empty, return raw body
        if (empty($descriptors)) {
            return $response->getBody();
        }

        // Legacy descriptor array support
        $statusCode = (string) $response->getHttpResponseCode();
        $descriptor = null;
        if (isset($descriptors[$statusCode])) {
            $descriptor = $descriptors[$statusCode];
        } elseif (isset($descriptors['default'])) {
            $descriptor = $descriptors['default'];
        }

        if ($descriptor === null || !isset($descriptor['type'])) {
            return $response->getBody();
        }

        return self::castValue($response->getBody(), $descriptor);
    }

    /**
     * Convert the payload to the descriptor type.
     *
     * @param mixed $value
     * @param array<string, mixed> $descriptor
     *
     * @return mixed
     */
    private static function castValue($value, array $descriptor)
    {
        switch ($descriptor['type']) {
            case 'class':
                if (!isset($descriptor['class'])) {
                    return $value;
                }

                return Hydrator::hydrate($value, ltrim($descriptor['class'], '\\'));
            case 'array':
                if (!is_array($value)) {
                    $value = $value instanceof \stdClass ? get_object_vars($value) : (array) $value;
                }

                if (!isset($descriptor['items']) || empty($descriptor['items'])) {
                    return $value;
                }

                $result = [];
                foreach ($value as $key => $item) {
                    $result[$key] = self::castValue($item, $descriptor['items']);
                }

                return $result;
            case 'scalar':
                return self::castScalar($value, isset($descriptor['scalar']) ? $descriptor['scalar'] : 'mixed');
            case 'object':
                if (is_array($value)) {
                    return $value;
                }

                if (is_object($value)) {
                    return get_object_vars($value);
                }

                return [];
            case 'void':
                return null;
            case 'mixed':
            default:
                return $value;
        }
    }

    /**
     * Cast scalar values to their expected PHP type.
     *
     * @param mixed $value
     * @param string $type
     *
     * @return mixed
     */
    private static function castScalar($value, $type)
    {
        switch ($type) {
            case 'string':
                return (string) $value;
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            default:
                return $value;
        }
    }

    /**
     * @param mixed $descriptors
     * @param int $statusCode
     *
     * @return bool
     */
    private static function hasDescriptorForStatus($descriptors, int $statusCode): bool
    {
        if (is_string($descriptors)) {
            return true;
        }

        if (!is_array($descriptors) || empty($descriptors)) {
            return false;
        }

        $status = (string) $statusCode;
        return isset($descriptors[$status]) || isset($descriptors['default']);
    }

    /**
     * @param int $statusCode
     *
     * @return string
     */
    private static function defaultErrorMessage(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'Server error';
        }

        return 'Client error';
    }

    /**
     * @param int $statusCode
     *
     * @return string
     */
    private static function defaultUnexpectedErrorMessage(int $statusCode): string
    {
        return sprintf('Unexpected API response (%d)', $statusCode);
    }

    /**
     * @param mixed $body
     * @param string $defaultMessage
     *
     * @return string
     */
    private static function extractErrorMessage($body, string $defaultMessage): string
    {
        foreach (['message', 'error_message', 'error_description', 'error'] as $key) {
            $value = self::readField($body, $key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $defaultMessage;
    }

    /**
     * @param mixed $payload
     * @param string $key
     *
     * @return mixed
     */
    private static function readField($payload, string $key)
    {
        if (is_array($payload) && array_key_exists($key, $payload)) {
            return $payload[$key];
        }
        if (is_object($payload) && isset($payload->{$key})) {
            return $payload->{$key};
        }

        return null;
    }
}
