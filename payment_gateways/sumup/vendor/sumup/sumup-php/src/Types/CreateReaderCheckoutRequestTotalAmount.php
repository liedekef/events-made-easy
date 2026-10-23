<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Amount structure.
 *
 * The amount is represented as an integer value altogether with the currency and the minor unit.
 *
 * For example, EUR 1.00 is represented as value 100 with minor unit of 2.
 *
 */
class CreateReaderCheckoutRequestTotalAmount
{
    /**
     * Currency ISO 4217 code
     *
     * @var string
     */
    public string $currency;

    /**
     * The minor units of the currency.
     * It represents the number of decimals of the currency. For the currencies CLP, COP and HUF, the minor unit is 0.
     *
     * @var int
     */
    public int $minorUnit;

    /**
     * Integer value of the amount.
     *
     * @var int
     */
    public int $value;

}
