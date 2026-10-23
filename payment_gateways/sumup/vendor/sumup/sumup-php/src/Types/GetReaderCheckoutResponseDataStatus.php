<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Current status of the checkout
 */
enum GetReaderCheckoutResponseDataStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
