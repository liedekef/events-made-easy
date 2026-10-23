<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Payment card details displayed on the receipt.
 */
class ReceiptCard
{
    /**
     * Last four digits of the payment card number.
     *
     * @var string|null
     */
    public ?string $last4Digits = null;

    /**
     * Issuing card network of the payment card.
     *
     * @var string|null
     */
    public ?string $type = null;

}
