<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Merchant-facing outcome of the payout record.
 */
enum FinancialPayoutStatus: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case FAILED = 'FAILED';
}
