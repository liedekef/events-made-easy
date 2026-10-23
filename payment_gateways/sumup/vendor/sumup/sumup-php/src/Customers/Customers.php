<?php

declare(strict_types=1);

namespace SumUp\Customers;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

class CustomersUpdateRequest
{
    /**
     * Personal details for the customer.
     *
     * @var \SumUp\Types\PersonalDetails|null
     */
    public ?\SumUp\Types\PersonalDetails $personalDetails = null;

    /**
     * Create request DTO.
     *
     * @param \SumUp\Types\PersonalDetails|null $personalDetails
     */
    public function __construct(
        ?\SumUp\Types\PersonalDetails $personalDetails = null
    ) {
        \SumUp\Hydrator::hydrate([
            'personal_details' => $personalDetails,
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

/**
 * Class Customers
 *
 * Customers represent payers in your integration. Create a customer with your own `customer_id` to associate their personal details and saved payment instruments with your business records.
 *
 * To save a card, create a checkout for that customer with `purpose = SETUP_RECURRING_PAYMENT`, then process it with the payer's consent and mandate details. See the [tokenization guide](https://developer.sumup.com/online-payments/guides/tokenization-with-payment-sdk/).
 *
 * Use the Customers endpoints to create, retrieve, or update customer details and to list or deactivate saved payment instruments. For subsequent payments, process a new checkout with the saved instrument's `token` and its associated `customer_id`.
 *
 * @package SumUp\Services
 */
class Customers implements SumUpService
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
     * Customers constructor.
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
     * Create a customer
     *
     * @param \SumUp\Types\Customer|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Customer
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function create(\SumUp\Types\Customer|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Customer
    {
        $path = '/v0.1/customers';
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\Customer::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\Customer::class],
        ], [
            '400' => ['type' => 'mixed'],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'POST', $path);
    }

    /**
     * Deactivate a payment instrument
     *
     * @param string $customerId The `customer_id` you supplied when creating the customer.
     * @param string $token Unique token identifying the card saved as a payment instrument resource.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return null
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function deactivatePaymentInstrument(string $customerId, string $token, ?RequestOptions $requestOptions = null): null
    {
        $path = sprintf('/v0.1/customers/%s/payment-instruments/%s', rawurlencode((string) $customerId), rawurlencode((string) $token));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('DELETE', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '204' => ['type' => 'void'],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'DELETE', $path);
    }

    /**
     * Retrieve a customer
     *
     * @param string $customerId The `customer_id` you supplied when creating the customer.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Customer
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $customerId, ?RequestOptions $requestOptions = null): \SumUp\Types\Customer
    {
        $path = sprintf('/v0.1/customers/%s', rawurlencode((string) $customerId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Customer::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'GET', $path);
    }

    /**
     * List payment instruments
     *
     * @param string $customerId The `customer_id` you supplied when creating the customer.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\PaymentInstrumentResponse[]
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function listPaymentInstruments(string $customerId, ?RequestOptions $requestOptions = null): array
    {
        $path = sprintf('/v0.1/customers/%s/payment-instruments', rawurlencode((string) $customerId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'array', 'items' => ['type' => 'class', 'class' => \SumUp\Types\PaymentInstrumentResponse::class]],
        ], [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'GET', $path);
    }

    /**
     * Update a customer
     *
     * @param string $customerId The `customer_id` you supplied when creating the customer.
     * @param CustomersUpdateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Customer
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function update(string $customerId, CustomersUpdateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Customer
    {
        $path = sprintf('/v0.1/customers/%s', rawurlencode((string) $customerId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = CustomersUpdateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PUT', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Customer::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'PUT', $path);
    }
}
