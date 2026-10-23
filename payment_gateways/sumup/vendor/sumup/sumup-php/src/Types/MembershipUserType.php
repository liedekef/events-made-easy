<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of the user account.
 */
enum MembershipUserType: string
{
    case USER = 'user';
    case MANAGED_USER = 'managed_user';
    case SERVICE_ACCOUNT = 'service_account';
    case SYSTEM_ACCOUNT = 'system_account';
}
