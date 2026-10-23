<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Payout plan of the registered user at the time when the transaction was made.
 */
enum TransactionMixinHistoryPayoutPlan: string
{
    case SINGLE_PAYMENT = 'SINGLE_PAYMENT';
    case TRUE_INSTALLMENT = 'TRUE_INSTALLMENT';
    case ACCELERATED_INSTALLMENT = 'ACCELERATED_INSTALLMENT';
}
