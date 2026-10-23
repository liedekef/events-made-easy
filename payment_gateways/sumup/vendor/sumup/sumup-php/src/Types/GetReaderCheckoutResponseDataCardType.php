<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of the card. Required for some countries
 */
enum GetReaderCheckoutResponseDataCardType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
