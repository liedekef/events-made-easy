<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of the payment. Required for some countries
 */
enum GetReaderCheckoutResponseDataPaymentType: string
{
    case CARD = 'card';
    case PIX = 'pix';
}
