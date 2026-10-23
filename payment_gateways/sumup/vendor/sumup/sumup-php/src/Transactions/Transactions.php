<?php

declare(strict_types=1);

namespace SumUp\Transactions;

namespace SumUp\Services;

use SumUp\HttpClient\HttpClientInterface;
use SumUp\HttpClient\RequestHeaders;
use SumUp\HttpClient\RequestOptions;
use SumUp\RequestEncoder;
use SumUp\ResponseDecoder;

/**
 * Optional amount for partial refunds of transactions.
 */
class TransactionsRefundRequest
{
    /**
     * Amount to refund in major units of the transaction's currency, for example `5` for EUR 5.00. It must be greater than zero and cannot exceed the amount eligible for a refund. Eligibility depends on the transaction and country/currency rules. If omitted, the system requests a full refund.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Create request DTO.
     *
     * @param float|null $amount
     */
    public function __construct(
        ?float $amount = null
    ) {
        \SumUp\Hydrator::hydrate([
            'amount' => $amount,
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

class TransactionsListResponse
{
    /**
     * Transactions in the current result page.
     *
     * @var \SumUp\Types\TransactionHistory[]|null
     */
    public ?array $items = null;

    /**
     * Pagination links for navigating the transaction history.
     *
     * @var \SumUp\Types\TransactionsHistoryLink[]|null
     */
    public ?array $links = null;

}

/**
 * Query parameters for TransactionsGetParams.
 *
 * @package SumUp\Services
 */
class TransactionsGetParams
{
    /**
     * Retrieves the transaction resource with the specified transaction ID (the `id` parameter in the transaction resource).
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * Retrieves the transaction resource with the specified transaction code.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * External transaction identifier supplied by the client.
     *
     * @var string|null
     */
    public ?string $foreignTransactionId = null;

    /**
     * Client-supplied identifier of the transaction.
     *
     * @var string|null
     */
    public ?string $clientTransactionId = null;

}

/**
 * Query parameters for TransactionsListParams.
 *
 * @package SumUp\Services
 */
class TransactionsListParams
{
    /**
     * Retrieves the transaction resource with the specified transaction code.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * Sort direction for the transaction history. Use `ascending` or `descending`; the default is `ascending`.
     *
     * @var string|null
     */
    public ?string $order = null;

    /**
     * Maximum number of transactions per page. Must be a positive integer. Defaults to `10` when omitted; a page can contain fewer results.
     *
     * @var int|null
     */
    public ?int $limit = null;

    /**
     * Filters transactions by user email. For multiple values, repeat the query parameter, for example `users[]=first@example.com&users[]=second@example.com`.
     *
     * @var string[]|null
     */
    public ?array $usersList = null;

    /**
     * Filters transactions by the listed final statuses. For multiple values, repeat the query parameter, for example `statuses[]=SUCCESSFUL&statuses[]=REFUNDED`.
     *
     * @var string[]|null
     */
    public ?array $statusesList = null;

    /**
     * Filters the returned results by the specified list of payment types used for the transactions.
     *
     * @var string[]|null
     */
    public ?array $paymentTypesList = null;

    /**
     * Filters the returned results by the specified list of entry modes.
     *
     * @var string[]|null
     */
    public ?array $entryModesList = null;

    /**
     * Filters the returned results by the specified list of transaction types.
     *
     * @var string[]|null
     */
    public ?array $typesList = null;

    /**
     * Filters the results by the latest modification time of resources and returns only transactions that are modified *at or after* the specified timestamp (in [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) format).
     *
     * @var string|null
     */
    public ?string $changesSince = null;

    /**
     * Filters the results by the creation time of resources and returns only transactions that are created *before* the specified timestamp (in [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) format).
     *
     * @var string|null
     */
    public ?string $newestTime = null;

    /**
     * Pagination reference that returns results before the specified reference. Use the value from a returned pagination link rather than constructing it yourself. This parameter takes precedence over `newest_time` when both are provided.
     *
     * @var string|null
     */
    public ?string $newestRef = null;

    /**
     * Filters the results by the creation time of resources and returns only transactions that are created *at or after* the specified timestamp (in [ISO8601](https://en.wikipedia.org/wiki/ISO_8601) format).
     *
     * @var string|null
     */
    public ?string $oldestTime = null;

    /**
     * Pagination reference that returns results after the specified reference. Use the value from a returned pagination link rather than constructing it yourself. This parameter takes precedence over `oldest_time` when both are provided.
     *
     * @var string|null
     */
    public ?string $oldestRef = null;

}

/**
 * Class Transactions
 *
 * Transactions represent completed or attempted payment operations processed for a merchant account. A transaction contains the core payment result, such as the amount, currency, payment method, creation time, and current high-level status.
 *
 * In addition to the main payment outcome, a transaction can contain related events that describe what happened after the original payment attempt. These events provide visibility into the financial lifecycle of the transaction, for example:
 * - `PAYOUT`: the payment being prepared for payout or included in a payout to the merchant
 * - `REFUND`: money returned to the payer
 * - `CHARGE_BACK`: money reversed after the original payment
 * - `PAYOUT_DEDUCTION`: an amount deducted from a payout to cover a refund or chargeback
 *
 * From an integrator's perspective, transactions are the authoritative record of payment outcomes. Use this tag to:
 * - list transactions for reporting, reconciliation, and customer support workflows
 * - retrieve a single transaction when you need the latest payment details
 * - inspect `simple_status` for the current merchant-facing outcome of the payment
 * - inspect `events` or `transaction_events` when you need refund, payout, or chargeback history
 *
 * Typical workflow:
 * - create and process payments through the Checkouts endpoints
 * - use the Transactions endpoints to read the resulting payment records
 * - use the returned statuses and events to update your own order, accounting, or support systems
 *
 * @package SumUp\Services
 */
class Transactions implements SumUpService
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
     * Transactions constructor.
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
     * Retrieve a transaction
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param TransactionsGetParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Types\TransactionFull
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function get(string $merchantCode, ?TransactionsGetParams $queryParams = null, ?RequestOptions $requestOptions = null): \SumUp\Types\TransactionFull
    {
        $path = sprintf('/v2.1/merchants/%s/transactions', rawurlencode((string) $merchantCode));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->id)) {
                $queryParamsData['id'] = $queryParams->id;
            }
            if (isset($queryParams->transactionCode)) {
                $queryParamsData['transaction_code'] = $queryParams->transactionCode;
            }
            if (isset($queryParams->foreignTransactionId)) {
                $queryParamsData['foreign_transaction_id'] = $queryParams->foreignTransactionId;
            }
            if (isset($queryParams->clientTransactionId)) {
                $queryParamsData['client_transaction_id'] = $queryParams->clientTransactionId;
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

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Types\TransactionFull::class, [
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
        ], 'GET', $path);
    }

    /**
     * List transactions
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param TransactionsListParams|null $queryParams Optional query string parameters
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return \SumUp\Services\TransactionsListResponse
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function list(string $merchantCode, ?TransactionsListParams $queryParams = null, ?RequestOptions $requestOptions = null): \SumUp\Services\TransactionsListResponse
    {
        $path = sprintf('/v2.1/merchants/%s/transactions/history', rawurlencode((string) $merchantCode));
        if ($queryParams !== null) {
            $queryParamsData = [];
            if (isset($queryParams->transactionCode)) {
                $queryParamsData['transaction_code'] = $queryParams->transactionCode;
            }
            if (isset($queryParams->order)) {
                $queryParamsData['order'] = $queryParams->order;
            }
            if (isset($queryParams->limit)) {
                $queryParamsData['limit'] = $queryParams->limit;
            }
            if (isset($queryParams->usersList)) {
                $queryParamsData['users[]'] = $queryParams->usersList;
            }
            if (isset($queryParams->statusesList)) {
                $queryParamsData['statuses[]'] = $queryParams->statusesList;
            }
            if (isset($queryParams->paymentTypesList)) {
                $queryParamsData['payment_types[]'] = $queryParams->paymentTypesList;
            }
            if (isset($queryParams->entryModesList)) {
                $queryParamsData['entry_modes[]'] = $queryParams->entryModesList;
            }
            if (isset($queryParams->typesList)) {
                $queryParamsData['types[]'] = $queryParams->typesList;
            }
            if (isset($queryParams->changesSince)) {
                $queryParamsData['changes_since'] = $queryParams->changesSince;
            }
            if (isset($queryParams->newestTime)) {
                $queryParamsData['newest_time'] = $queryParams->newestTime;
            }
            if (isset($queryParams->newestRef)) {
                $queryParamsData['newest_ref'] = $queryParams->newestRef;
            }
            if (isset($queryParams->oldestTime)) {
                $queryParamsData['oldest_time'] = $queryParams->oldestTime;
            }
            if (isset($queryParams->oldestRef)) {
                $queryParamsData['oldest_ref'] = $queryParams->oldestRef;
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

        return ResponseDecoder::decodeOrThrow($response, \SumUp\Services\TransactionsListResponse::class, [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Error::class],
            '401' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'GET', $path);
    }

    /**
     * Refund a transaction
     *
     * @param string $merchantCode Short unique identifier for the merchant.
     * @param string $transactionId Unique identifier of the transaction.
     * @param TransactionsRefundRequest|array<string, mixed>|null $body Optional request payload
     * @param RequestOptions|null $requestOptions Optional typed request options
     *
     * @return array<string, mixed>
     * @throws \SumUp\Exception\ApiException
     * @throws \SumUp\Exception\UnexpectedApiException
     * @throws \SumUp\Exception\ConnectionException
     * @throws \SumUp\Exception\SDKException
     */
    public function refund(string $merchantCode, string $transactionId, TransactionsRefundRequest|array|null $body = null, ?RequestOptions $requestOptions = null): array
    {
        $path = sprintf('/v1.0/merchants/%s/payments/%s/refunds', rawurlencode((string) $merchantCode), rawurlencode((string) $transactionId));
        $payload = [];
        if ($body !== null) {
            $requestBody = $body;
            if (is_array($requestBody)) {
                $requestBody = TransactionsRefundRequest::fromArray($requestBody);
            }
            $payload = RequestEncoder::encode($requestBody);
        }
        $headers = RequestHeaders::build($this->accessToken, $requestOptions);

        $response = $this->client->send('POST', $path, $payload, $headers, $requestOptions);

        return ResponseDecoder::decodeOrThrow($response, [
            '201' => ['type' => 'object'],
        ], [
            '400' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '403' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '404' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '409' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
            '422' => ['type' => 'class', 'class' => \SumUp\Types\Problem::class],
        ], 'POST', $path);
    }
}
