<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * High-level status of the transaction from the merchant's perspective.
 *
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
 */
enum TransactionFullSimpleStatus: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case PAID_OUT = 'PAID_OUT';
    case CANCEL_FAILED = 'CANCEL_FAILED';
    case CANCELLED = 'CANCELLED';
    case CHARGEBACK = 'CHARGEBACK';
    case FAILED = 'FAILED';
    case REFUND_FAILED = 'REFUND_FAILED';
    case REFUNDED = 'REFUNDED';
    case NON_COLLECTION = 'NON_COLLECTION';
    case PENDING = 'PENDING';
}
