<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Business address of the merchant.
 */
class ReceiptMerchantDataMerchantProfileAddress
{
    /**
     * First line of the merchant address.
     *
     * @var string|null
     */
    public ?string $addressLine1 = null;

    /**
     * Second line of the merchant address.
     *
     * @var string|null
     */
    public ?string $addressLine2 = null;

    /**
     * City of the merchant address.
     *
     * @var string|null
     */
    public ?string $city = null;

    /**
     * Two-letter ISO 3166-1 alpha-2 country code of the merchant address.
     *
     * @var string|null
     */
    public ?string $country = null;

    /**
     * English name of the country in the merchant address.
     *
     * @var string|null
     */
    public ?string $countryEnName = null;

    /**
     * Localized name of the country in the merchant address.
     *
     * @var string|null
     */
    public ?string $countryNativeName = null;

    /**
     * Region or state of the merchant address.
     *
     * @var string|null
     */
    public ?string $regionName = null;

    /**
     * Postal code of the merchant address.
     *
     * @var string|null
     */
    public ?string $postCode = null;

    /**
     * Landline phone number of the merchant.
     *
     * @var string|null
     */
    public ?string $landline = null;

}
