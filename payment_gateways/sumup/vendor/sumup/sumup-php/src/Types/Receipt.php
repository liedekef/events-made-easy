<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Structured receipt details for a transaction. The transaction's `amount`, `vat_amount`, and `tip_amount`, as well as event amounts, are returned as decimal strings in major currency units, for example `"10.10"` for EUR 10.10.
 */
class Receipt
{
    /**
     * Transaction details displayed on a receipt.
     *
     * @var ReceiptTransaction|null
     */
    public ?ReceiptTransaction $transactionData = null;

    /**
     * Merchant details displayed on a transaction receipt.
     *
     * @var ReceiptMerchantData|null
     */
    public ?ReceiptMerchantData $merchantData = null;

    /**
     * EMV-specific metadata returned for card-present payments.
     *
     * @var array<string, mixed>|null
     */
    public ?array $emvData = null;

    /**
     * Acquirer-specific metadata related to the card authorization.
     *
     * @var ReceiptAcquirerData|null
     */
    public ?ReceiptAcquirerData $acquirerData = null;

}
