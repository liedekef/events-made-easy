<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Core details shared by transaction resources.
 */
class TransactionBase
{
    /**
     * Unique identifier of the transaction.
     *
     * @var string|null
     */
    public ?string $id = null;

    /**
     * SumUp transaction code, for example `TEENSK4W2K`. Use it to look up the transaction with the `transaction_code` query parameter. This is separate from the transaction's `id` and the card issuer's `auth_code`.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * Total amount of the transaction in major units of `currency`, for example `10.1` for EUR 10.10.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Three-letter [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency code of the amount.
     *
     * @var TransactionBaseCurrency|null
     */
    public ?TransactionBaseCurrency $currency = null;

    /**
     * The timestamp of when the transaction was created.
     *
     * @var string|null
     */
    public ?string $timestamp = null;

    /**
     * Current status of the transaction.
     * - `PENDING`: The transaction has been created but its final outcome is not known yet.
     * - `SUCCESSFUL`: The transaction completed successfully.
     * - `CANCELLED`: The transaction was cancelled or otherwise reversed before completion.
     * - `FAILED`: The transaction attempt did not complete successfully.
     * - `REFUNDED`: The transaction was refunded in full or in part.
     *
     * @var TransactionBaseStatus|null
     */
    public ?TransactionBaseStatus $status = null;

    /**
     * Payment category recorded on a transaction, for example `POS` for a point-of-sale card payment, `ECOM` for an online card payment, or `RECURRING` for a recurring card payment. These reporting values are separate from the lowercase `payment_type` values used to process checkouts.
     *
     * @var TransactionBasePaymentType|null
     */
    public ?TransactionBasePaymentType $paymentType = null;

    /**
     * Number of installments for a deferred payment.
     *
     * @var int|null
     */
    public ?int $installmentsCount = null;

}
