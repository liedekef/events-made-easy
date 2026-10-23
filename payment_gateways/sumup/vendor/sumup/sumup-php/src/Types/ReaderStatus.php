<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * The status of the reader object gives information about the current state of the reader.
 *
 * Possible values:
 *
 * - `unknown` - The reader status is unknown.
 * - `processing` - The reader is created and waits for the physical device to confirm the pairing.
 * - `paired` - The reader is paired with a merchant account and can be used with SumUp APIs.
 * - `expired` - The pairing is expired and no longer usable with the account. The resource needs to get recreated.
 */
enum ReaderStatus: string
{
    case UNKNOWN = 'unknown';
    case PROCESSING = 'processing';
    case PAIRED = 'paired';
    case EXPIRED = 'expired';
}
