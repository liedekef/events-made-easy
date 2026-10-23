<?php

declare(strict_types=1);

namespace SumUp\Types;

class GetReaderCheckoutResponseData
{
    /**
     * Type of the card. Required for some countries
     *
     * @var GetReaderCheckoutResponseDataCardType|null
     */
    public ?GetReaderCheckoutResponseDataCardType $cardType;

    /**
     * Unique identifier for the checkout
     *
     * @var string
     */
    public string $checkoutId;

    /**
     * Client transaction identifier associated with the checkout
     *
     * @var string
     */
    public string $clientTransactionId;

    /**
     * Checkout creation timestamp
     *
     * @var string
     */
    public string $createdAt;

    /**
     * Number of installments for the transaction. Required for some countries.
     *
     * @var int|null
     */
    public ?int $installments;

    /**
     * Payment failure reason
     *
     * @var string|null
     */
    public ?string $paymentFailureReason = null;

    /**
     * Payment status from payments v2 event
     *
     * @var string|null
     */
    public ?string $paymentStatus;

    /**
     * Type of the payment. Required for some countries
     *
     * @var GetReaderCheckoutResponseDataPaymentType
     */
    public GetReaderCheckoutResponseDataPaymentType $paymentType;

    /**
     * Reader firmware version
     *
     * @var string
     */
    public string $readerFirmwareVersion;

    /**
     * Device serial number
     *
     * @var string
     */
    public string $readerSerialNumber;

    /**
     * Current status of the checkout
     *
     * @var GetReaderCheckoutResponseDataStatus
     */
    public GetReaderCheckoutResponseDataStatus $status;

    /**
     * Amount structure.
     * The amount is represented as an integer value altogether with the currency and the minor unit.
     * For example, EUR 1.00 is represented as value 100 with minor unit of 2.
     *
     * @var GetReaderCheckoutResponseDataTotalAmount
     */
    public GetReaderCheckoutResponseDataTotalAmount $totalAmount;

    /**
     * Checkout last update timestamp
     *
     * @var string
     */
    public string $updatedAt;

    /**
     * Checkout expiration timestamp. After this time, the checkout will be automatically cancelled.
     *
     * @var string|null
     */
    public ?string $validUntil;

}
