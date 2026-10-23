<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Checkout-specific fields associated with a transaction.
 */
class TransactionCheckoutInfo
{
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
     * @var TransactionCheckoutInfoEntryMode|null
     */
    public ?TransactionCheckoutInfoEntryMode $entryMode = null;

    /**
     * Authorization code for the transaction sent by the payment card issuer or bank. Applicable only to card payments.
     *
     * @var string|null
     */
    public ?string $authCode = null;

}
