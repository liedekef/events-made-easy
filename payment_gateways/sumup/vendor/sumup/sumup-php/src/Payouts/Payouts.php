<?php

declare(strict_types=1);

namespace SumUp\Payouts;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\ResponseDecoder;

/**
 * Query parameters for PayoutsListParams.
 *
 * @package SumUp\Services
 */
class PayoutsListParams
{
    /**
     * Start date of the payout period filter, inclusive, in [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) `date` format (`YYYY-MM-DD`).
     *
     * @var string
     */
    public string $startDate;

    /**
     * End date of the payout period filter, inclusive, in [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) `date` format (`YYYY-MM-DD`). Must be greater than or equal to `start_date`.
     *
     * @var string
     */
    public string $endDate;

    /**
     * Response format for the payout list.
     *
     * @var string|null
     */
    public ?string $format = null;

    /**
     * Maximum number of payout records to return.
     *
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * Sort direction for the returned payouts.
     *
     * @var string|null
     */
    public ?string $order = null;

}

/**
 * Class Payouts
 *
 * The Payouts model will allow you to track funds you’ve received from SumUp.
 *
 * You can receive a detailed payouts list with information like dates, fees, references and statuses, using the `List payouts` endpoint.
 *
 * @package SumUp\Services
 */
class Payouts implements SumUpService
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
     * Payouts constructor.
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
     * List payouts
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param PayoutsListParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\FinancialPayout[]
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(string $merchantCode, ?PayoutsListParams $queryParams = null, ?RequestOptions $requestOptions = null): array
    {
        $path = sprintf('/v1.0/merchants/%s/payouts', rawurlencode((string) $merchantCode));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->startDate)) {
                $queryParamsData['start_date'] = $queryParams->startDate;
            }
            if (isset($queryParams->endDate)) {
                $queryParamsData['end_date'] = $queryParams->endDate;
            }
            if (isset($queryParams->format)) {
                $queryParamsData['format'] = $queryParams->format;
            }
            if (isset($queryParams->limit)) {
                $queryParamsData['limit'] = $queryParams->limit;
            }
            if (isset($queryParams->order)) {
                $queryParamsData['order'] = $queryParams->order;
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
            '200' => ['type' => 'array', 'items' => ['type' => 'class', 'class' => \SumUp\Types\FinancialPayout::class]],
        ], [
            '400' => ['type' => 'array', 'items' => ['type' => 'class', 'class' => \SumUp\Types\ErrorExtended::class]],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }
}
