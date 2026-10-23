<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Merchant profile details displayed on the receipt.
 */
class ReceiptMerchantDataMerchantProfile
{
    /**
     * Short unique identifier for the merchant.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

    /**
     * Business name of the merchant.
     *
     * @var string|null
     */
    public ?string $businessName = null;

    /**
     * Company registration number of the merchant.
     *
     * @var string|null
     */
    public ?string $companyRegistrationNumber = null;

    /**
     * VAT identification number of the merchant.
     *
     * @var string|null
     */
    public ?string $vatId = null;

    /**
     * Website of the merchant.
     *
     * @var string|null
     */
    public ?string $website = null;

    /**
     * Email address of the merchant.
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * Language configured for the merchant profile.
     *
     * @var string|null
     */
    public ?string $language = null;

    /**
     * Business address of the merchant.
     *
     * @var ReceiptMerchantDataMerchantProfileAddress|null
     */
    public ?ReceiptMerchantDataMerchantProfileAddress $address = null;

}
