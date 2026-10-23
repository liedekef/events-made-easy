<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * How the payment details were captured, for example `CHIP` or `CONTACTLESS` for card-present payments and `CUSTOMER_ENTRY` for card details entered by the payer. For wallet and alternative payment methods, this can identify the method, such as `APPLE_PAY` or `BLIK`.
 */
enum TransactionCheckoutInfoEntryMode: string
{
    case BOLETO = 'BOLETO';
    case SOFORT = 'SOFORT';
    case IDEAL = 'IDEAL';
    case BANCONTACT = 'BANCONTACT';
    case EPS = 'EPS';
    case MYBANK = 'MYBANK';
    case SATISPAY = 'SATISPAY';
    case BLIK = 'BLIK';
    case P_24 = 'P24';
    case GIROPAY = 'GIROPAY';
    case PIX = 'PIX';
    case QR_CODE_PIX = 'QR_CODE_PIX';
    case APPLE_PAY = 'APPLE_PAY';
    case GOOGLE_PAY = 'GOOGLE_PAY';
    case PAYPAL = 'PAYPAL';
    case TWINT = 'TWINT';
    case NONE = 'NONE';
    case CHIP = 'CHIP';
    case MANUAL_ENTRY = 'MANUAL_ENTRY';
    case CUSTOMER_ENTRY = 'CUSTOMER_ENTRY';
    case MAGSTRIPE_FALLBACK = 'MAGSTRIPE_FALLBACK';
    case MAGSTRIPE = 'MAGSTRIPE';
    case DIRECT_DEBIT = 'DIRECT_DEBIT';
    case CONTACTLESS = 'CONTACTLESS';
    case MOTO = 'MOTO';
    case CONTACTLESS_MAGSTRIPE = 'CONTACTLESS_MAGSTRIPE';
    case N_A = 'N/A';
}
