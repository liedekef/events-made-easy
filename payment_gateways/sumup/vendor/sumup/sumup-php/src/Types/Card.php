<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * __Required when payment type is `card`.__ Details of the payment card.
 */
class Card
{
    /**
     * Name of the cardholder as it appears on the payment card.
     *
     * @var string
     */
    public string $name;

    /**
     * Number of the payment card (without spaces).
     *
     * @var string
     */
    public string $number;

    /**
     * Two- or four-digit expiration year in `YY` or `YYYY` format.
     *
     * @var string
     */
    public string $expiryYear;

    /**
     * Two-digit expiration month, from `01` through `12`.
     *
     * @var string
     */
    public string $expiryMonth;

    /**
     * Three or four-digit card verification value (security code) of the payment card.
     *
     * @var string
     */
    public string $cvv;

    /**
     * Required five-digit ZIP code. Applicable only to merchant users in the USA.
     *
     * @var string|null
     */
    public ?string $zipCode = null;

    /**
     * Issuing card network of the payment card used for the transaction.
     *
     * @var CardType
     */
    public CardType $type;

}
