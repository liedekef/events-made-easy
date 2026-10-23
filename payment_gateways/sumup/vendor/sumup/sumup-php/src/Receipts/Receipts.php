<?php

declare(strict_types=1);

namespace SumUp\Receipts;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\ResponseDecoder;

/**
 * Query parameters for ReceiptsGetParams.
 *
 * @package SumUp\Services
 */
class ReceiptsGetParams
{
    /**
     * Short unique identifier for the merchant.
     *
     * @var string
     */
    public string $mid;

    /**
     * Unique identifier of the transaction event to include on the receipt.
     *
     * @var int|null
     */
    public ?int $txEventId = null;

}

/**
 * Class Receipts
 *
 * Retrieve structured receipt data for a transaction, including payment, merchant, and acquirer details. Use this data to display a receipt in your application. The response is JSON, rather than a rendered receipt document.
 *
 * @package SumUp\Services
 */
class Receipts implements SumUpService
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
     * Receipts constructor.
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
     * Retrieve receipt details
     *
     * @param string $transactionId SumUp unique transaction ID or transaction code, e.g. TS7HDYLSKD.
     * @param ReceiptsGetParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\Receipt
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $transactionId, ?ReceiptsGetParams $queryParams = null, ?RequestOptions $requestOptions = null): \SumUp\Types\Receipt
    {
        $path = sprintf('/v1.1/receipts/%s', rawurlencode((string) $transactionId));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->mid)) {
                $queryParamsData['mid'] = $queryParams->mid;
            }
            if (isset($queryParams->txEventId)) {
                $queryParamsData['tx_event_id'] = $queryParams->txEventId;
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

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\Receipt::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'GET', $path);
    }
}
