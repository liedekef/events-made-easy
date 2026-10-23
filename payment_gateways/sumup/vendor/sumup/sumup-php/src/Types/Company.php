<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Information about the company or business. This is legal information that is used for verification.
 *
 */
class Company
{
    /**
     * The company's legal name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The merchant category code for the account as specified by [ISO18245](https://www.iso.org/standard/33365.html). MCCs are used to classify businesses based on the goods or services they provide.
     *
     * @var string|null
     */
    public ?string $merchantCategoryCode = null;

    /**
     * The category identifying the legal structure of the company or legal entity.
     *
     * @var string|null
     */
    public ?string $legalType = null;

    /**
     * The company's primary address.
     *
     * @var Address|null
     */
    public ?Address $address = null;

    /**
     * A trading address is where your suppliers, banks or customers send you correspondence to. Trading address can be different to the company's registered address (`address`).
     *
     * @var Address|null
     */
    public ?Address $tradingAddress = null;

    /**
     * A list of country-specific company identifiers.
     *
     * @var CompanyIdentifier[]|null
     */
    public ?array $identifiers = null;

    /**
     * The company's phone number (used for verification) in [E.164](https://en.wikipedia.org/wiki/E.164) format.
     *
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * HTTP(S) URL of the company's website.
     *
     * @var string|null
     */
    public ?string $website = null;

    /**
     * Object attributes that are modifiable only by SumUp applications.
     *
     * @var array<string, mixed>|null
     */
    public ?array $attributes = null;

}
