<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Profile's personal address information.
 */
class AddressLegacy
{
    /**
     * City name from the address.
     *
     * @var string|null
     */
    public ?string $city = null;

    /**
     * Two letter country code formatted according to [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2).
     *
     * @var string|null
     */
    public ?string $country = null;

    /**
     * First line of the address with details of the street name and number.
     *
     * @var string|null
     */
    public ?string $line1 = null;

    /**
     * Second line of the address with details of the building, unit, apartment, and floor numbers.
     *
     * @var string|null
     */
    public ?string $line2 = null;

    /**
     * Postal code from the address.
     *
     * @var string|null
     */
    public ?string $postalCode = null;

    /**
     * State name or abbreviation from the address.
     *
     * @var string|null
     */
    public ?string $state = null;

}
