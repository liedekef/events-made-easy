<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Saved payer details identified by the `customer_id` supplied by your integration. A customer can have saved payment instruments for subsequent payments.
 */
class Customer
{
    /**
     * Identifier you supply when creating the customer. Use an ID from your own system and retain it for subsequent customer, checkout, and saved-payment-instrument requests.
     *
     * @var string
     */
    public string $customerId;

    /**
     * Personal details for the customer.
     *
     * @var PersonalDetails|null
     */
    public ?PersonalDetails $personalDetails = null;

    /**
     * Create request DTO.
     *
     * @param string $customerId
     * @param PersonalDetails|null $personalDetails
     */
    public function __construct(
        string $customerId,
        ?PersonalDetails $personalDetails = null
    ) {
        \SumUp\Hydrator::hydrate([
            'customer_id' => $customerId,
            'personal_details' => $personalDetails,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        self::assertRequiredFields($data, [
            'customer_id' => 'customerId',
        ]);

        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $requiredFields
     */
    private static function assertRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $serializedName => $propertyName) {
            if (!array_key_exists($serializedName, $data) && !array_key_exists($propertyName, $data)) {
                throw new \InvalidArgumentException(sprintf('Missing required field "%s".', $serializedName));
            }
        }
    }

}
