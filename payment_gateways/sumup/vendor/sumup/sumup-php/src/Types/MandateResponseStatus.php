<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Current lifecycle status of the mandate.
 */
enum MandateResponseStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
