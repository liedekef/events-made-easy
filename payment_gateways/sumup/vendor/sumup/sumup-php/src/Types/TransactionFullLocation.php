<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the payment location as received from the payment terminal.
 */
class TransactionFullLocation
{
    /**
     * Latitude value from the coordinates of the payment location (as received from the payment terminal reader).
     *
     * @var float|null
     */
    public ?float $lat = null;

    /**
     * Longitude value from the coordinates of the payment location (as received from the payment terminal reader).
     *
     * @var float|null
     */
    public ?float $lon = null;

    /**
     * Indication of the precision of the geographical position received from the payment terminal.
     *
     * @var float|null
     */
    public ?float $horizontalAccuracy = null;

}
