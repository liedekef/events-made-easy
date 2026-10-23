<?php

declare(strict_types=1);

namespace SumUp\Checkouts;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

class CheckoutsCreateApplePaySessionRequest
{
    /**
     * Hostname of the website displaying the Apple Pay payment sheet, without a URL scheme or path. Use the domain registered for Apple Pay.
     *
     * @var string
     */
    public string $context;

    /**
     * Apple Pay validation URL received as `validationURL` in the browser's `onvalidatemerchant` event.
     *
     * @var string
     */
    public string $target;

    /**
     * Create request DTO.
     *
     * @param string $context
     * @param string $target
     */
    public function __construct(
        string $context,
        string $target
    ) {
        \SumUp\Hydrator::hydrate([
            'context' => $context,
            'target' => $target,
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
            'context' => 'context',
            'target' => 'target',
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

class CheckoutsListAvailablePaymentMethodsResponse
{
    /**
     * Payment methods available to the merchant for the checkout.
     *
     * @var CheckoutsListAvailablePaymentMethodsResponseItem[]|null
     */
    public ?array $availablePaymentMethods = null;

}

class CheckoutsListAvailablePaymentMethodsResponseItem
{
    /**
     * Unique identifier of the payment method.
     *
     * @var string
     */
    public string $id;

}

/**
 * Query parameters for CheckoutsListParams.
 *
 * @package SumUp\Services
 */
class CheckoutsListParams
{
    /**
     * Filters checkouts by the merchant-defined `checkout_reference` supplied when creating the checkout. This is separate from the SumUp-generated checkout `id`.
     *
     * @var string|null
     */
    public ?string $checkoutReference = null;

}

/**
 * Query parameters for CheckoutsListAvailablePaymentMethodsParams.
 *
 * @package SumUp\Services
 */
class CheckoutsListAvailablePaymentMethodsParams
{
    /**
     * Payment amount in major units, for example `9.99` for EUR 9.99. When filtering by `amount`, also provide `currency`.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Three-letter ISO 4217 currency code for which the payment methods should be eligible, for example `EUR`.
     *
     * @var string|null
     */
    public ?string $currency = null;

}

/**
 * Class Checkouts
 *
 * Checkouts represent online payment sessions that you create before attempting to charge a payer. A checkout captures the payment intent, such as the amount, currency, merchant, and optional customer or redirect settings, and then moves through its lifecycle as you process it.
 *
 * Use this tag to:
 * - create a checkout before collecting or confirming payment details
 * - process the checkout with a card, saved card, wallet, or supported alternative payment method
 * - retrieve or list checkouts to inspect their current state and associated payment attempts
 * - deactivate a checkout that should no longer be used
 *
 * Typical workflow:
 * - create a checkout with the order amount, currency, and merchant information
 * - process the checkout through SumUp client tools such as the [Payment Widget and Swift Checkout SDK](https://developer.sumup.com/online-payments/checkouts)
 * - retrieve the checkout or use the Transactions endpoints to inspect the resulting payment record
 *
 * Checkouts are used to initiate and orchestrate online payments. Transactions remain the authoritative record of the resulting payment outcome.
 *
 * @package SumUp\Services
 */
class Checkouts implements SumUpService
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
     * Checkouts constructor.
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
     * Create a checkout
     *
     * @param \SumUp\Types\CheckoutCreateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Checkout
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function create(\SumUp\Types\CheckoutCreateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Checkout
    {
        $path = '/v0.1/checkouts';
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\CheckoutCreateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'class', 'class' => \SumUp\Types\Checkout::class],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\ErrorExtended::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\ErrorForbidden::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'POST', $path);
    }

    /**
     * Create an Apple Pay session
     *
     * @param string $checkoutId SumUp-generated `id` returned when creating a checkout. Use this value to retrieve, update, process, or deactivate the checkout.
     * @param CheckoutsCreateApplePaySessionRequest|array<string, mixed>|null $body Optional request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return array<string, mixed>
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function createApplePaySession(string $checkoutId, CheckoutsCreateApplePaySessionRequest|array|null $body = null, ?RequestOptions $requestOptions = null): array
    {
        $path = sprintf('/v0.2/checkouts/%s/apple-pay-session', rawurlencode((string) $checkoutId));
        $payload = [];
        if ($body !== null) {
            $requestBody = $body;
            if (is_array($requestBody)) {
                $requestBody = CheckoutsCreateApplePaySessionRequest::fromArray($requestBody);
            }
            $payload = RequestEncoder::encode($requestBody);
        }
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PUT', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'object'],
        ], [
            '400' => ['type' => 'mixed'],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'PUT', $path);
    }

    /**
     * Deactivate a checkout
     *
     * @param string $checkoutId SumUp-generated `id` returned when creating a checkout. Use this value to retrieve, update, process, or deactivate the checkout.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Checkout
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function deactivate(string $checkoutId, ?RequestOptions $requestOptions = null): \SumUp\Types\Checkout
    {
        $path = sprintf('/v0.1/checkouts/%s', rawurlencode((string) $checkoutId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('DELETE', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Checkout::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'DELETE', $path);
    }

    /**
     * Retrieve a checkout
     *
     * @param string $checkoutId SumUp-generated `id` returned when creating a checkout. Use this value to retrieve, update, process, or deactivate the checkout.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\CheckoutSuccess
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $checkoutId, ?RequestOptions $requestOptions = null): \SumUp\Types\CheckoutSuccess
    {
        $path = sprintf('/v0.1/checkouts/%s', rawurlencode((string) $checkoutId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\CheckoutSuccess::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'GET', $path);
    }

    /**
     * List checkouts
     *
     * @param CheckoutsListParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\CheckoutSuccess[]
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(?CheckoutsListParams $queryParams = null, ?RequestOptions $requestOptions = null): array
    {
        $path = '/v0.1/checkouts';
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->checkoutReference)) {
                $queryParamsData['checkout_reference'] = $queryParams->checkoutReference;
            }
            if (!empty($queryParamsData)) {
                $queryString = http_build_query($queryParamsData);
                if (!empty($queryString)) {
                    $path .= '?' . $queryString;
                }
            }
        }
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'array', 'items' => ['type' => 'class', 'class' => \SumUp\Types\CheckoutSuccess::class]],
        ], [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Get available payment methods
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param CheckoutsListAvailablePaymentMethodsParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Services\CheckoutsListAvailablePaymentMethodsResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function listAvailablePaymentMethods(string $merchantCode, ?CheckoutsListAvailablePaymentMethodsParams $queryParams = null, ?RequestOptions $requestOptions = null): \SumUp\Services\CheckoutsListAvailablePaymentMethodsResponse
    {
        $path = sprintf('/v0.1/merchants/%s/payment-methods', rawurlencode((string) $merchantCode));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->amount)) {
                $queryParamsData['amount'] = $queryParams->amount;
            }
            if (isset($queryParams->currency)) {
                $queryParamsData['currency'] = $queryParams->currency;
            }
            if (!empty($queryParamsData)) {
                $queryString = http_build_query($queryParamsData);
                if (!empty($queryString)) {
                    $path .= '?' . $queryString;
                }
            }
        }
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Services\CheckoutsListAvailablePaymentMethodsResponse::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\DetailsError::class],
        ], 'GET', $path);
    }

    /**
     * Process a checkout
     *
     * @param string $checkoutId SumUp-generated `id` returned when creating a checkout. Use this value to retrieve, update, process, or deactivate the checkout.
     * @param \SumUp\Types\ProcessCheckout|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return mixed|\SumUp\Types\CheckoutAccepted
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function process(string $checkoutId, \SumUp\Types\ProcessCheckout|array $body, ?RequestOptions $requestOptions = null)
    {
        $path = sprintf('/v0.1/checkouts/%s', rawurlencode((string) $checkoutId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\ProcessCheckout::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PUT', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '200' => ['type' => 'mixed'],
            '202' => ['type' => 'class', 'class' => \SumUp\Types\CheckoutAccepted::class],
        ], [
            '400' => ['type' => 'mixed'],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'PUT', $path);
    }

    /**
     * Update a checkout
     *
     * @param string $checkoutId SumUp-generated `id` returned when creating a checkout. Use this value to retrieve, update, process, or deactivate the checkout.
     * @param \SumUp\Types\CheckoutUpdateRequest|array<string, mixed> $body Required request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Checkout
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function update(string $checkoutId, \SumUp\Types\CheckoutUpdateRequest|array $body, ?RequestOptions $requestOptions = null): \SumUp\Types\Checkout
    {
        $path = sprintf('/v0.1/checkouts/%s', rawurlencode((string) $checkoutId));
        $payload = [];
        $requestBody = $body;
        if (is_array($requestBody)) {
            $requestBody = \SumUp\Types\CheckoutUpdateRequest::fromArray($requestBody);
        }
        $payload = RequestEncoder::encode($requestBody);
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('PATCH', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Checkout::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'PATCH', $path);
    }
}
