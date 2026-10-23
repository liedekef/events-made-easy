<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of the payment instrument.
 */
enum PaymentInstrumentResponseType: string
{
    case CARD = 'card';
}
