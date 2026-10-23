<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Transaction details displayed on a receipt.
 */
class ReceiptTransaction
{
    /**
     * Transaction code returned after processing the transaction.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

    /**
     * Unique identifier of the transaction.
     *
     * @var string|null
     */
    public ?string $transactionId = null;

    /**
     * Short unique identifier for the merchant.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

    /**
     * Total transaction amount, in major units.
     *
     * @var string|null
     */
    public ?string $amount = null;

    /**
     * VAT included in the transaction amount, in major units.
     *
     * @var string|null
     */
    public ?string $vatAmount = null;

    /**
     * Tip included in the transaction amount, in major units.
     *
     * @var string|null
     */
    public ?string $tipAmount = null;

    /**
     * Three-letter ISO 4217 currency code of the transaction.
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
     * Current processing status of the transaction.
     *
     * @var string|null
     */
    public ?string $status = null;

    /**
     * Payment type used for the transaction.
     *
     * @var string|null
     */
    public ?string $paymentType = null;

    /**
     * Entry mode of the payment details.
     *
     * @var string|null
     */
    public ?string $entryMode = null;

    /**
     * Cardholder verification method.
     *
     * @var string|null
     */
    public ?string $verificationMethod = null;

    /**
     * Card reader details displayed on the receipt.
     *
     * @var ReceiptReader|null
     */
    public ?ReceiptReader $cardReader = null;

    /**
     * Payment card details displayed on the receipt.
     *
     * @var ReceiptCard|null
     */
    public ?ReceiptCard $card = null;

    /**
     * Number of installments.
     *
     * @var int|null
     */
    public ?int $installmentsCount = null;

    /**
     * Whether the transaction was processed as credit or debit.
     *
     * @var ReceiptTransactionProcessAs|null
     */
    public ?ReceiptTransactionProcessAs $processAs = null;

    /**
     * Products associated with the transaction.
     *
     * @var array<string, mixed>[]|null
     */
    public ?array $products = null;

    /**
     * VAT breakdown for the transaction.
     *
     * @var array<string, mixed>[]|null
     */
    public ?array $vatRates = null;

    /**
     * Transaction events displayed on the receipt.
     *
     * @var ReceiptEvent[]|null
     */
    public ?array $events = null;

    /**
     * Receipt number associated with the transaction.
     *
     * @var string|null
     */
    public ?string $receiptNo = null;

}
