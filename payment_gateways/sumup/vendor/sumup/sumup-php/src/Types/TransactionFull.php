<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Full transaction resource with checkout, payout, and event details.
 */
class TransactionFull
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
     * Unique code of the registered merchant to whom the payment is made.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

    /**
     * VAT included in the total transaction amount, in major units of the transaction's currency.
     *
     * @var float|null
     */
    public ?float $vatAmount = null;

    /**
     * Tip included in the total transaction amount, in major units of the transaction's currency.
     *
     * @var float|null
     */
    public ?float $tipAmount = null;

    /**
     * How the payment details were captured, for example `CHIP` or `CONTACTLESS` for card-present payments and `CUSTOMER_ENTRY` for card details entered by the payer. For wallet and alternative payment methods, this can identify the method, such as `APPLE_PAY` or `BLIK`.
     *
     * @var string|null
     */
    public ?string $entryMode = null;

    /**
     * Authorization code for the transaction sent by the payment card issuer or bank. Applicable only to card payments.
     *
     * @var string|null
     */
    public ?string $authCode = null;

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

    /**
     * Email address of the registered user (merchant) to whom the payment is made.
     *
     * @var string|null
     */
    public ?string $username = null;

    /**
     * Total SumUp transaction fee in major units of the transaction's currency.
     *
     * @var float|null
     */
    public ?float $feeAmount = null;

    /**
     * Latitude value from the coordinates of the payment location (as received from the payment terminal reader).
     *
     * @var float|null
     */
    public ?float $lat = null;

    /**
     * Longitude value from the coordinates of the payment location (as received from the payment terminal reader).
     *
     * @var float|null
     */
    public ?float $lon = null;

    /**
     * Indication of the precision of the geographical position received from the payment terminal.
     *
     * @var float|null
     */
    public ?float $horizontalAccuracy = null;

    /**
     * Internal SumUp identifier of the merchant.
     *
     * @var int|null
     */
    public ?int $merchantId = null;

    /**
     * Details of the device used to create the transaction.
     *
     * @var Device|null
     */
    public ?Device $deviceInfo = null;

    /**
     * Simple name of the payment type.
     *
     * @var TransactionFullSimplePaymentType|null
     */
    public ?TransactionFullSimplePaymentType $simplePaymentType = null;

    /**
     * Verification method used for the transaction.
     *
     * @var TransactionFullVerificationMethod|null
     */
    public ?TransactionFullVerificationMethod $verificationMethod = null;

    /**
     * Details of the payment card.
     *
     * @var CardResponse|null
     */
    public ?CardResponse $card = null;

    /**
     * Details of the ELV card account associated with the transaction.
     *
     * @var ElvCardAccount|null
     */
    public ?ElvCardAccount $elvAccount = null;

    /**
     * Local timestamp of when the transaction was created.
     *
     * @var string|null
     */
    public ?string $localTime = null;

    /**
     * The date of the payout.
     *
     * @var string|null
     */
    public ?string $payoutDate = null;

    /**
     * Payout type for the transaction.
     *
     * @var TransactionFullPayoutType|null
     */
    public ?TransactionFullPayoutType $payoutType = null;

    /**
     * Whether the transaction was processed as credit or debit.
     *
     * @var TransactionFullProcessAs|null
     */
    public ?TransactionFullProcessAs $processAs = null;

    /**
     * List of products from the merchant's catalogue for which the transaction serves as a payment.
     *
     * @var Product[]|null
     */
    public ?array $products = null;

    /**
     * List of VAT rates applicable to the transaction.
     *
     * @var array<string, mixed>[]|null
     */
    public ?array $vatRates = null;

    /**
     * Detailed list of events related to the transaction.
     *
     * @var TransactionEvent[]|null
     */
    public ?array $transactionEvents = null;

    /**
     * High-level status of the transaction from the merchant's perspective.
     * - `PENDING`: The payment has been initiated and is still being processed. A final outcome is not available yet.
     * - `SUCCESSFUL`: The payment was completed successfully.
     * - `PAID_OUT`: The payment was completed successfully and the funds have already been included in a payout to the merchant.
     * - `FAILED`: The payment did not complete successfully.
     * - `CANCELLED`: The payment was cancelled or reversed and is no longer payable or payable to the merchant.
     * - `CANCEL_FAILED`: An attempt to cancel or reverse the payment was not completed successfully.
     * - `REFUNDED`: The payment was refunded in full or in part.
     * - `REFUND_FAILED`: An attempt to refund the payment was not completed successfully.
     * - `CHARGEBACK`: The payment was subject to a chargeback.
     * - `NON_COLLECTION`: The amount could not be collected from the merchant after a chargeback or related adjustment.
     *
     * @var TransactionFullSimpleStatus|null
     */
    public ?TransactionFullSimpleStatus $simpleStatus = null;

    /**
     * List of hyperlinks for accessing related resources.
     *
     * @var Link[]|null
     */
    public ?array $links = null;

    /**
     * Compact list of events related to the transaction.
     *
     * @var Event[]|null
     */
    public ?array $events = null;

    /**
     * Details of the payment location as received from the payment terminal.
     *
     * @var TransactionFullLocation|null
     */
    public ?TransactionFullLocation $location = null;

    /**
     * Indicates whether tax deduction is enabled for the transaction.
     *
     * @var bool|null
     */
    public ?bool $taxEnabled = null;

}
