<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Financial event associated with a transaction.
 *
 * - `PAYOUT`: Funds from the transaction being prepared for or included in a merchant payout. Check the event status to determine whether they have been paid out.
 * - `REFUND`: Money returned to the payer.
 * - `CHARGE_BACK`: A reversal of the payment following a chargeback.
 * - `PAYOUT_DEDUCTION`: An amount deducted from a merchant payout, for example to cover a refund or chargeback.
 */
enum ReceiptEventType: string
{
    case PAYOUT = 'PAYOUT';
    case CHARGE_BACK = 'CHARGE_BACK';
    case REFUND = 'REFUND';
    case PAYOUT_DEDUCTION = 'PAYOUT_DEDUCTION';
}
