<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Key indicating type of error. Present only for typed 401 responses (e.g. invalid token, invalid password). Absent for generic unauthorized responses.
 */
enum UnauthorizedErrorsType: string
{
    case INVALID_ACCESS_TOKEN = 'INVALID_ACCESS_TOKEN';
    case INVALID_PASSWORD = 'INVALID_PASSWORD';
}
