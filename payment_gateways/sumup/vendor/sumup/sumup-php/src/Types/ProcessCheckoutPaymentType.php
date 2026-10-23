<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Payment method used for this processing attempt. It determines which additional request fields are required.
 */
enum ProcessCheckoutPaymentType: string
{
    case CARD = 'card';
    case BOLETO = 'boleto';
    case IDEAL = 'ideal';
    case BLIK = 'blik';
    case BANCONTACT = 'bancontact';
    case GOOGLE_PAY = 'google_pay';
    case APPLE_PAY = 'apple_pay';
}
