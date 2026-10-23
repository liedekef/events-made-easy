<?php

declare(strict_types=1);

namespace SumUp\Types;

class Person
{
    /**
     * The unique identifier for the Person. This is a [typeid](https://github.com/sumup/typeid).
     *
     * @var string
     */
    public string $id;

    /**
     * A corresponding identity user ID for the Person, if they have a user account.
     *
     * @var string|null
     */
    public ?string $userId = null;

    /**
     * The date of birth of the individual, represented as an ISO 8601:2004 [ISO8601‑2004] YYYY-MM-DD format.
     *
     * @var string|null
     */
    public ?string $birthdate = null;

    /**
     * The first name(s) of the individual.
     *
     * @var string|null
     */
    public ?string $givenName = null;

    /**
     * The last name(s) of the individual.
     *
     * @var string|null
     */
    public ?string $familyName = null;

    /**
     * Middle name(s) of the End-User. Note that in some cultures, people can have multiple middle names; all can be present, with the names being separated by space characters. Also note that in some cultures, middle names are not used.
     *
     * @var string|null
     */
    public ?string $middleName = null;

    /**
     * The (mobile) phone number of the individual (used for verification) in [E.164](https://en.wikipedia.org/wiki/E.164) format.
     *
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * A list of roles the Person has in the Merchant or towards SumUp. A Merchant must have at least one Person with the relationship `representative`.
     *
     * @var string[]|null
     */
    public ?array $relationships = null;

    /**
     * Details about the ownership relationship between the Person and the Merchant. This is only set if the Person has a relationship of type `owner`.
     *
     * @var Ownership|null
     */
    public ?Ownership $ownership = null;

    /**
     * The address of the individual.
     *
     * @var Address|null
     */
    public ?Address $address = null;

    /**
     * A list of country-specific personal identifiers.
     *
     * @var PersonalIdentifier[]|null
     */
    public ?array $identifiers = null;

    /**
     * The Alpha-2 ISO code of the country where the Person is a citizen.
     *
     * @var string|null
     */
    public ?string $citizenship = null;

    /**
     * The Person's nationality. May be an [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) country code, but legacy data may not conform to this standard.
     *
     * @var string|null
     */
    public ?string $nationality = null;

    /**
     * An [ISO3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) country code representing the country where the Person resides.
     *
     * @var string|null
     */
    public ?string $countryOfResidence = null;

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

}
