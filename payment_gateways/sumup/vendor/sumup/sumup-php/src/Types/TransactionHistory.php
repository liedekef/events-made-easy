<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Transaction entry returned in history listing responses.
 */
class TransactionHistory
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
     * @var string|null
     */
    public ?string $currency = null;

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
     * @var string|null
     */
    public ?string $status = null;

    /**
     * Payment category recorded on a transaction, for example `POS` for a point-of-sale card payment, `ECOM` for an online card payment, or `RECURRING` for a recurring card payment. These reporting values are separate from the lowercase `payment_type` values used to process checkouts.
     *
     * @var string|null
     */
    public ?string $paymentType = null;

    /**
     * Number of installments for a deferred payment.
     *
     * @var int|null
     */
    public ?int $installmentsCount = null;

    /**
     * Short description of the payment. The value is taken from the `description` property of the related checkout resource.
     *
     * @var string|null
     */
    public ?string $productSummary = null;

    /**
     * Total number of payouts to the registered user specified in the `user` property.
     *
     * @var int|null
     */
    public ?int $payoutsTotal = null;

    /**
     * Number of payouts that are made to the registered user specified in the `user` property.
     *
     * @var int|null
     */
    public ?int $payoutsReceived = null;

    /**
     * Payout plan of the registered user at the time when the transaction was made.
     *
     * @var string|null
     */
    public ?string $payoutPlan = null;

    /**
     * Unique identifier of the transaction.
     *
     * @var string|null
     */
    public ?string $transactionId = null;

    /**
     * Client-supplied identifier of the transaction.
     *
     * @var string|null
     */
    public ?string $clientTransactionId = null;

    /**
     * Email address of the registered user (merchant) to whom the payment is made.
     *
     * @var string|null
     */
    public ?string $user = null;

    /**
     * Type of the transaction for the registered user specified in the `user` property.
     *
     * @var TransactionHistoryType|null
     */
    public ?TransactionHistoryType $type = null;

    /**
     * Issuing card network of the payment card used for the transaction.
     *
     * @var TransactionHistoryCardType|null
     */
    public ?TransactionHistoryCardType $cardType = null;

    /**
     * Payout date (if paid out at once).
     *
     * @var string|null
     */
    public ?string $payoutDate = null;

    /**
     * Payout type.
     *
     * @var TransactionHistoryPayoutType|null
     */
    public ?TransactionHistoryPayoutType $payoutType = null;

    /**
     * Total amount refunded for this transaction, in major units of the transaction's currency.
     *
     * @var float|null
     */
    public ?float $refundedAmount = null;

}
