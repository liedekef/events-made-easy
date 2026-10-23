<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * A single payout-related record.
 *
 * A record can represent either:
 * - an actual payout sent to the merchant (`type = PAYOUT`)
 * - a deduction applied against merchant funds for a refund, chargeback, direct debit return, or balance adjustment
 */
class FinancialPayout
{
    /**
     * Unique identifier of the payout-related record.
     *
     * @var int
     */
    public int $id;

    /**
     * High-level payout record category.
     *
     * @var FinancialPayoutType
     */
    public FinancialPayoutType $type;

    /**
     * Amount of the payout or deduction in major units.
     *
     * @var float
     */
    public float $amount;

    /**
     * Payout date associated with the record, in `YYYY-MM-DD` format.
     *
     * @var string
     */
    public string $date;

    /**
     * Three-letter ISO 4217 currency code of the payout.
     *
     * @var string
     */
    public string $currency;

    /**
     * Fee amount associated with the payout record, in major units.
     *
     * @var float
     */
    public float $fee;

    /**
     * Merchant-facing outcome of the payout record.
     *
     * @var FinancialPayoutStatus
     */
    public FinancialPayoutStatus $status;

    /**
     * Processor or payout reference associated with the record.
     *
     * @var string
     */
    public string $reference;

    /**
     * Transaction code of the original sale associated with the payout or deduction.
     *
     * @var string
     */
    public string $transactionCode;

}
