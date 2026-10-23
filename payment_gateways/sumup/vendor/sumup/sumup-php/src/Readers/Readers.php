<?php

declare(strict_types=1);

namespace SumUp\Readers;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

class ReadersCreateRequest
{
    /**
     * The pairing code is a 8 or 9 character alphanumeric string that is displayed on a SumUp Device after initiating the pairing. It is used to link the physical device to the created pairing.
     *
     * @var string
     */
    public string $pairingCode;

    /**
     * Custom human-readable, user-defined name for easier identification of the reader.
     *
     * @var string
     */
    public string $name;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Create request DTO.
     *
     * @param string $pairingCode
     * @param string $name
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        string $pairingCode,
        string $name,
        ?array $metadata = null
    ) {
        \SumUp\Hydrator::hydrate([
            'pairing_code' => $pairingCode,
            'name' => $name,
            'metadata' => $metadata,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        self::assertRequiredFields($data, [
            'pairing_code' => 'pairingCode',
            'name' => 'name',
        ]);

        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $requiredFields
     */
    private static function assertRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $serializedName => $propertyName) {
            if (!array_key_exists($serializedName, $data) && !array_key_exists($propertyName, $data)) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s".', $serializedName));
            }
        }
    }

}

/**
 * Request payload for ReadersTerminateCheckoutRequest.
 *
 * @package SumUp\Services
 */
class ReadersTerminateCheckoutRequest
{
    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self();
    }
}

class ReadersUpdateRequest
{
    /**
     * Custom human-readable, user-defined name for easier identification of the reader.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Create request DTO.
     *
     * @param string|null $name
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        ?string $name = null,
        ?array $metadata = null
    ) {
        \SumUp\Hydrator::hydrate([
            'name' => $name,
            'metadata' => $metadata,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

}

class ReadersListResponse
{
    /**
     *
     * @var \SumUp\Types\Reader[]
     */
    public array $items;

}

/**
 * Class Readers
 *
 * A reader represents a device that accepts payments. You can use the SumUp Solo to accept in-person payments.
 *
 * @package SumUp\Services
 */
class Readers implements SumUpService
{
    /**
     * The client for the http communication.
     *
     * @var HttpClientInterface
     */
    protected HttpClientInterface $client;

    /**
     * The access token needed for authentication for the services.
     *
     * @var string
     */
    protected string $accessToken;

    /**
     * Readers constructor.
     *
     * @param HttpClientInterface $client
     * @param string $accessToken
     */
    public function __construct(HttpClientInterface $client, string $accessToken)
    {
        $this->client = $client;
        $this->accessToken = $accessToken;
    }

    /**
     * Create a Reader
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param ReadersCreateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Reader
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function create(string $merchantCode, ReadersCreateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Reader
    {
        $path = sprintf('/v0.1/merchants/%s/readers', rawurlencode((string) $merchantCode));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = ReadersCreateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\Reader::class],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Create a Reader Checkout
     *
     * @param string $merchantCode Merchant Code
     * @param string $readerId The unique identifier of the Reader
     * @param \SumUp\Types\CreateReaderCheckoutRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\CreateReaderCheckoutResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function createCheckout(string $merchantCode, string $readerId, \SumUp\Types\CreateReaderCheckoutRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\CreateReaderCheckoutResponse
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s/checkout', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\CreateReaderCheckoutRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\CreateReaderCheckoutResponse::class],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '422' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Create a Go Reader Payment
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $readerId The unique identifier of the reader.
     * @param \SumUp\Types\ReaderPaymentRequestParams|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\ReaderPaymentResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function createGoCheckout(string $merchantCode, string $readerId, \SumUp\Types\ReaderPaymentRequestParams|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\ReaderPaymentResponse
    {
        $path = sprintf('/v0/merchants/%s/readers/%s/go-checkout', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\ReaderPaymentRequestParams::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\ReaderPaymentResponse::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '422' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Delete a reader
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $readerId The unique identifier of the reader.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return null
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function delete(string $merchantCode, string $readerId, ?RequestOptions $requestOptions = null): null
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('DELETE', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'void'],
        ], [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'DELETE', $path);
    }

    /**
     * Retrieve a Reader
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $readerId The unique identifier of the reader.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Reader
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $merchantCode, string $readerId, ?RequestOptions $requestOptions = null): \SumUp\Types\Reader
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Reader::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Get a Reader Checkout
     *
     * @param string $merchantCode Merchant Code
     * @param string $readerId The unique identifier of the Reader
     * @param string $checkoutId The unique identifier of the Checkout
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\GetReaderCheckoutResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function getCheckout(string $merchantCode, string $readerId, string $checkoutId, ?RequestOptions $requestOptions = null): \SumUp\Types\GetReaderCheckoutResponse
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s/checkout/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId), rawurlencode((string) $checkoutId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\GetReaderCheckoutResponse::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Get a Reader Status
     *
     * @param string $merchantCode Merchant Code
     * @param string $readerId The unique identifier of the Reader
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\StatusResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function getStatus(string $merchantCode, string $readerId, ?RequestOptions $requestOptions = null): \SumUp\Types\StatusResponse
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s/status', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\StatusResponse::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * List Readers
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Services\ReadersListResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(string $merchantCode, ?RequestOptions $requestOptions = null): \SumUp\Services\ReadersListResponse
    {
        $path = sprintf('/v0.1/merchants/%s/readers', rawurlencode((string) $merchantCode));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Services\ReadersListResponse::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Terminate a Reader Checkout
     *
     * @param string $merchantCode Merchant Code
     * @param string $readerId The unique identifier of the Reader
     * @param ReadersTerminateCheckoutRequest|array<string, mixed>|null $body Optional request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return null
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function terminateCheckout(string $merchantCode, string $readerId, ReadersTerminateCheckoutRequest|array|null $body = null, ?RequestOptions $requestOptions = null): null
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s/terminate', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        if ($body !== null) {
            $requestBody = $body;
            if (is_array($requestBody)) {
                $requestBody = ReadersTerminateCheckoutRequest::fromArray($requestBody);
            }
            $payload = RequestEncoder::encode($requestBody);
        }
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '202' => ['type' => 'void'],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '422' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }

    /**
     * Update a Reader
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $readerId The unique identifier of the reader.
     * @param ReadersUpdateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Reader
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function update(string $merchantCode, string $readerId, ReadersUpdateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Reader
    {
        $path = sprintf('/v0.1/merchants/%s/readers/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $readerId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = ReadersUpdateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PATCH', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Reader::class, [
            '403' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'PATCH', $path);
    }
}
