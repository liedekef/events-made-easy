<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Payout type for the transaction.
 */
enum TransactionFullPayoutType: string
{
    case BANK_ACCOUNT = 'BANK_ACCOUNT';
    case PREPAID_CARD = 'PREPAID_CARD';
}
