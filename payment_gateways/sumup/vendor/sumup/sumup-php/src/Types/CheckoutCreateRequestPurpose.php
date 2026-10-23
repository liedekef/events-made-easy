<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Business purpose of the checkout. Use `CHECKOUT` for a standard payment and `SETUP_RECURRING_PAYMENT` when collecting consent and payment details for future recurring charges.
 */
enum CheckoutCreateRequestPurpose: string
{
    case CHECKOUT = 'CHECKOUT';
    case SETUP_RECURRING_PAYMENT = 'SETUP_RECURRING_PAYMENT';
}
