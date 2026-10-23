<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of the transaction for the registered user specified in the `user` property.
 */
enum TransactionHistoryType: string
{
    case PAYMENT = 'PAYMENT';
    case REFUND = 'REFUND';
    case CHARGE_BACK = 'CHARGE_BACK';
}
