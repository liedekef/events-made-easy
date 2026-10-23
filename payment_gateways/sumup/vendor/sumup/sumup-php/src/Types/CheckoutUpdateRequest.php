<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Request body for updating an existing checkout. Include only the fields that should be changed.
 */
class CheckoutUpdateRequest
{
    /**
     * Updated amount to be charged to the payer, expressed in major units.
     *
     * @var float|null
     */
    public ?float $amount = null;

    /**
     * Three-letter [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency code of the amount.
     *
     * @var CheckoutUpdateRequestCurrency|null
     */
    public ?CheckoutUpdateRequestCurrency $currency = null;

    /**
     * Updated short merchant-defined description shown in SumUp tools and reporting.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Updated merchant-defined reference for the checkout.
     *
     * @var string|null
     */
    public ?string $checkoutReference = null;

    /**
     * Updated expiration timestamp. The checkout must be processed before this moment, otherwise it becomes unusable.
     *
     * @var string|null
     */
    public ?string $validUntil = null;

    /**
     * Updated merchant-scoped customer identifier associated with the checkout.
     *
     * @var string|null
     */
    public ?string $customerId = null;

    /**
     * Create request DTO.
     *
     * @param float|null $amount
     * @param CheckoutUpdateRequestCurrency|string|null $currency
     * @param string|null $description
     * @param string|null $checkoutReference
     * @param string|null $validUntil
     * @param string|null $customerId
     */
    public function __construct(
        ?float $amount = null,
        CheckoutUpdateRequestCurrency|string|null $currency = null,
        ?string $description = null,
        ?string $checkoutReference = null,
        ?string $validUntil = null,
        ?string $customerId = null
    ) {
        \SumUp\Hydrator::hydrate([
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'checkout_reference' => $checkoutReference,
            'valid_until' => $validUntil,
            'customer_id' => $customerId,
        ], self::class, $this);
    }

    /**
     * Create request DTO from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $request = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        \SumUp\Hydrator::hydrate($data, self::class, $request);

        return $request;
    }

}
