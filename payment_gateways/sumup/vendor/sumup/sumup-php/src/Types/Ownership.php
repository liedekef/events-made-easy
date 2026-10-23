<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details about the ownership relationship between the Person and the Merchant. This is only set if the Person has a relationship of type `owner`.
 *
 */
class Ownership
{
    /**
     * The percent of ownership shares held by the Person expressed in percent mille (1/100000). Only Persons with the relationship `owner` can have ownership.
     *
     * @var int
     */
    public int $share;

}
