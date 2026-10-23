<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * High-level payout record category.
 */
enum FinancialPayoutType: string
{
    case PAYOUT = 'PAYOUT';
    case CHARGE_BACK_DEDUCTION = 'CHARGE_BACK_DEDUCTION';
    case REFUND_DEDUCTION = 'REFUND_DEDUCTION';
    case DD_RETURN_DEDUCTION = 'DD_RETURN_DEDUCTION';
    case BALANCE_DEDUCTION = 'BALANCE_DEDUCTION';
}
