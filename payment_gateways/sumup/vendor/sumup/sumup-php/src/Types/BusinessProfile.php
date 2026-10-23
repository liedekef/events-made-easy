<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Business information about the merchant. This information will be visible to the merchant's customers.
 *
 */
class BusinessProfile
{
    /**
     * The customer-facing business name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The descriptor is the text that your customer sees on their bank account statement.
     * The more recognisable your descriptor is, the less risk you have of receiving disputes (e.g. chargebacks).
     *
     * @var string|null
     */
    public ?string $dynamicDescriptor = null;

    /**
     * The business's publicly available website.
     *
     * @var string|null
     */
    public ?string $website = null;

    /**
     * A publicly available email address.
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * A publicly available phone number in [E.164](https://en.wikipedia.org/wiki/E.164) format.
     *
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * An address somewhere in the world. The address fields used depend on the country conventions. For example, in Great Britain, `city` is `post_town`. In the United States, the top-level administrative unit used in addresses is `state`, whereas in Chile it's `region`.
     * Whether an address is valid or not depends on whether the locally required fields are present. Fields not supported in a country will be ignored.
     *
     * @var Address|null
     */
    public ?Address $address = null;

    /**
     * Settings used to apply the Merchant's branding to email receipts, invoices, checkouts, and other products.
     *
     * @var Branding|null
     */
    public ?Branding $branding = null;

}
