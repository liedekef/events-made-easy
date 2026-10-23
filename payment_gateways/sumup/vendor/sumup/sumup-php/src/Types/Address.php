<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * The company's primary address.
 */
class Address
{
    /**
     *
     * @var string[]|null
     */
    public ?array $streetAddress = null;

    /**
     * The postal code (aka. zip code) of the address.
     *
     * @var string|null
     */
    public ?string $postCode = null;

    /**
     * The ISO3166-1 Alpha-2 code of the address country.
     *
     * @var string
     */
    public string $country;

    /**
     * The city of the address.
     *
     * @var string|null
     */
    public ?string $city = null;

    /**
     * The province where the address is located. This may not be relevant in some countries.
     *
     * @var string|null
     */
    public ?string $province = null;

    /**
     * The region where the address is located. This may not be relevant in some countries.
     *
     * @var string|null
     */
    public ?string $region = null;

    /**
     * A county is a geographic region of a country used for administrative or other purposes in some nations. Used in countries such as Ireland, Romania, etc.
     *
     * @var string|null
     */
    public ?string $county = null;

    /**
     * In Spain, an autonomous community is the first sub-national level of political and administrative division.
     *
     * @var string|null
     */
    public ?string $autonomousCommunity = null;

    /**
     * A post town is a required part of all postal addresses in the United Kingdom and Ireland, and a basic unit of the postal delivery system.
     *
     * @var string|null
     */
    public ?string $postTown = null;

    /**
     * Most often, a country has a single state, with various administrative divisions. The term "state" is sometimes used to refer to the federated polities that make up the federation. Used in countries such as the United States and Brazil.
     *
     * @var string|null
     */
    public ?string $state = null;

    /**
     * Locality level of the address. Used in countries such as Brazil or Chile.
     *
     * @var string|null
     */
    public ?string $neighborhood = null;

    /**
     * In many countries, terms cognate with "commune" are used, referring to the community living in the area and the common interest. Used in countries such as Chile.
     *
     * @var string|null
     */
    public ?string $commune = null;

    /**
     * A department (French: département, Spanish: departamento) is an administrative or political division in several countries. Used in countries such as Colombia.
     *
     * @var string|null
     */
    public ?string $department = null;

    /**
     * A municipality is usually a single administrative division having corporate status and powers of self-government or jurisdiction as granted by national and regional laws to which it is subordinate. Used in countries such as Colombia.
     *
     * @var string|null
     */
    public ?string $municipality = null;

    /**
     * A district is a type of administrative division that in some countries is managed by the local government. Used in countries such as Portugal.
     *
     * @var string|null
     */
    public ?string $district = null;

    /**
     * A US system of postal codes used by the United States Postal Service (USPS).
     *
     * @var string|null
     */
    public ?string $zipCode = null;

    /**
     * A postal address in Ireland.
     *
     * @var string|null
     */
    public ?string $eircode = null;

}
