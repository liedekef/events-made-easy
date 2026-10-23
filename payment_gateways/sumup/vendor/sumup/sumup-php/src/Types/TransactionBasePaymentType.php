<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Payment category recorded on a transaction, for example `POS` for a point-of-sale card payment, `ECOM` for an online card payment, or `RECURRING` for a recurring card payment. These reporting values are separate from the lowercase `payment_type` values used to process checkouts.
 */
enum TransactionBasePaymentType: string
{
    case CASH = 'CASH';
    case POS = 'POS';
    case ECOM = 'ECOM';
    case RECURRING = 'RECURRING';
    case BITCOIN = 'BITCOIN';
    case BALANCE = 'BALANCE';
    case MOTO = 'MOTO';
    case BOLETO = 'BOLETO';
    case DIRECT_DEBIT = 'DIRECT_DEBIT';
    case APM = 'APM';
    case UNKNOWN = 'UNKNOWN';
}
