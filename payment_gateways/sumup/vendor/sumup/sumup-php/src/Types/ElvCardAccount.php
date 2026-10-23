<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the ELV card account associated with the transaction.
 */
class ElvCardAccount
{
    /**
     * ELV card sort code.
     *
     * @var string|null
     */
    public ?string $sortCode = null;

    /**
     * ELV card account number last 4 digits.
     *
     * @var string|null
     */
    public ?string $last4Digits = null;

    /**
     * ELV card sequence number.
     *
     * @var int|null
     */
    public ?int $sequenceNo = null;

    /**
     * ELV IBAN.
     *
     * @var string|null
     */
    public ?string $iban = null;

}
