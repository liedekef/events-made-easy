<?php

declare(strict_types=1);

namespace SumUp\Types;

class Merchant
{
    /**
     * Short unique identifier for the merchant.
     *
     * @var string
     */
    public string $merchantCode;

    /**
     * ID of the organization the merchant belongs to (if any).
     *
     * @var string|null
     */
    public ?string $organizationId = null;

    /**
     * The business type.
     * * `sole_trader`: The business is run by an self-employed individual.
     * * `company`: The business is run as a company with one or more shareholders
     * * `partnership`: The business is run as a company with two or more shareholders that can be also other legal entities
     * * `non_profit`: The business is run as a nonprofit organization that operates for public or social benefit
     * * `government_entity`: The business is state owned and operated
     *
     * @var string|null
     */
    public ?string $businessType = null;

    /**
     * Information about the company or business. This is legal information that is used for verification.
     *
     * @var Company|null
     */
    public ?Company $company = null;

    /**
     * An [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2)
     * country code. This definition users `oneOf` with a two-character string
     * type to allow for support of future countries in client code.
     *
     * @var string
     */
    public string $country;

    /**
     * Business information about the merchant. This information will be visible to the merchant's customers.
     *
     * @var BusinessProfile|null
     */
    public ?BusinessProfile $businessProfile = null;

    /**
     * A user-facing small-format logo for use in dashboards and other user-facing applications. For customer-facing branding see `merchant.business_profile.branding`.
     *
     * @var string|null
     */
    public ?string $avatar = null;

    /**
     * A user-facing name of the merchant account for use in dashboards and other user-facing applications. For customer-facing business name see `merchant.business_profile`.
     *
     * @var string|null
     */
    public ?string $alias = null;

    /**
     * Three-letter [ISO currency code](https://en.wikipedia.org/wiki/ISO_4217) representing the default currency for the account.
     *
     * @var string
     */
    public string $defaultCurrency;

    /**
     * Merchant's default locale, represented as a BCP47 [RFC5646](https://datatracker.ietf.org/doc/html/rfc5646) language tag. This is typically an ISO 639-1 Alpha-2 [ISO639‑1](https://www.iso.org/iso-639-language-code) language code in lowercase and an ISO 3166-1 Alpha-2 [ISO3166‑1](https://www.iso.org/iso-3166-country-codes.html) country code in uppercase, separated by a dash. For example, en-US or fr-CA.
     * In multilingual countries this is the merchant's preferred locale out of those, that are officially spoken in the country. In a countries with a single official language this will match the official language.
     *
     * @var string
     */
    public string $defaultLocale;

    /**
     * True if the merchant is a sandbox for testing.
     *
     * @var bool|null
     */
    public ?bool $sandbox = null;

    /**
     * A set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
     * **Warning**: Updating Meta will overwrite the existing data. Make sure to always include the complete JSON object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $meta = null;

    /**
     *
     * @var ClassicMerchantIdentifiers|null
     */
    public ?ClassicMerchantIdentifiers $classic = null;

    /**
     * The version of the resource. The version reflects a specific change submitted to the API via one of the `PATCH` endpoints.
     *
     * @var string|null
     */
    public ?string $version = null;

    /**
     * Reflects the status of changes submitted through the `PATCH` endpoints for the Merchant or Persons. If some changes have not been applied yet, the status will be `pending`. If all changes have been applied, the status `done`.
     * The status is only returned after write operations or on read endpoints when the `version` query parameter is provided.
     *
     * @var string|null
     */
    public ?string $changeStatus = null;

    /**
     * The date and time when the resource was created. This is a string as defined in [RFC 3339, section 5.6](https://datatracker.ietf.org/doc/html/rfc3339#section-5.6).
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The date and time when the resource was last updated. This is a string as defined in [RFC 3339, section 5.6](https://datatracker.ietf.org/doc/html/rfc3339#section-5.6).
     *
     * @var string
     */
    public string $updatedAt;

}
