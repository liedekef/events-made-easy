<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Three-letter [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency code of the amount.
 */
enum CheckoutCurrency: string
{
    case BRL = 'BRL';
    case CHF = 'CHF';
    case CLP = 'CLP';
    case COP = 'COP';
    case CZK = 'CZK';
    case DKK = 'DKK';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case HRK = 'HRK';
    case HUF = 'HUF';
    case NOK = 'NOK';
    case PLN = 'PLN';
    case RON = 'RON';
    case SEK = 'SEK';
    case USD = 'USD';
}
