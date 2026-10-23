<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Verification method used for the transaction.
 */
enum TransactionFullVerificationMethod: string
{
    case NONE = 'none';
    case SIGNATURE = 'signature';
    case OFFLINE_PIN = 'offline PIN';
    case ONLINE_PIN = 'online PIN';
    case OFFLINE_PIN_PLUS_SIGNATURE = 'offline PIN + signature';
    case NA = 'na';
}
