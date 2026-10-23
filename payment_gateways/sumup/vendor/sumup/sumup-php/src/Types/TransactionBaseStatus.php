<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Current status of the transaction.
 *
 * - `PENDING`: The transaction has been created but its final outcome is not known yet.
 * - `SUCCESSFUL`: The transaction completed successfully.
 * - `CANCELLED`: The transaction was cancelled or otherwise reversed before completion.
 * - `FAILED`: The transaction attempt did not complete successfully.
 * - `REFUNDED`: The transaction was refunded in full or in part.
 */
enum TransactionBaseStatus: string
{
    case SUCCESSFUL = 'SUCCESSFUL';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
    case REFUNDED = 'REFUNDED';
}
