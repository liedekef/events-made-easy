<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Simple name of the payment type.
 */
enum TransactionFullSimplePaymentType: string
{
    case CASH = 'CASH';
    case CC_SIGNATURE = 'CC_SIGNATURE';
    case ELV = 'ELV';
    case ELV_WITHOUT_SIGNATURE = 'ELV_WITHOUT_SIGNATURE';
    case CC_CUSTOMER_ENTERED = 'CC_CUSTOMER_ENTERED';
    case MANUAL_ENTRY = 'MANUAL_ENTRY';
    case EMV = 'EMV';
    case RECURRING = 'RECURRING';
    case BALANCE = 'BALANCE';
    case MOTO = 'MOTO';
    case BOLETO = 'BOLETO';
    case APM = 'APM';
    case BITCOIN = 'BITCOIN';
    case CARD = 'CARD';
}
