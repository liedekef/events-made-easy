<?php

declare(strict_types=1);

namespace SumUp\Merchants;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\ResponseDecoder;

/**
 * Class Merchants
 *
 * A Merchant represents a single business which can use SumUp products like payment processing.
 *
 * @package SumUp\Services
 */
class Merchants implements SumUpService
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
     * Merchants constructor.
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
     * Get Merchant
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Merchant
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $merchantCode, ?RequestOptions $requestOptions = null): \SumUp\Types\Merchant
    {
        $path = sprintf('/v1/merchants/%s', rawurlencode((string) $merchantCode));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Merchant::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Get Person
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $personId Person ID
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Person
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function getPerson(string $merchantCode, string $personId, ?RequestOptions $requestOptions = null): \SumUp\Types\Person
    {
        $path = sprintf('/v1/merchants/%s/persons/%s', rawurlencode((string) $merchantCode), rawurlencode((string) $personId));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Person::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * List Persons
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\ListPersonsResponseBody
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function listPersons(string $merchantCode, ?RequestOptions $requestOptions = null): \SumUp\Types\ListPersonsResponseBody
    {
        $path = sprintf('/v1/merchants/%s/persons', rawurlencode((string) $merchantCode));
        $payload = [];
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('GET', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\ListPersonsResponseBody::class, [
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }
}
