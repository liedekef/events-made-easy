<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Request body for creating a checkout before processing payment. Define the payment amount, currency, merchant, and optional customer or redirect behavior here.
 */
class CheckoutCreateRequest
{
    /**
     * Merchant-defined reference for the new checkout, up to 64 characters. Use it to correlate the checkout with an order or payment attempt in your own system. If a checkout already exists for the supplied unique parameters, creation returns `409` with `DUPLICATED_CHECKOUT`; see the conflict response.
     *
     * @var string
     */
    public string $checkoutReference;

    /**
     * Amount to be charged to the payer, expressed in major units.
     *
     * @var float
     */
    public float $amount;

    /**
     * Three-letter [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217) currency code of the amount.
     *
     * @var CheckoutCreateRequestCurrency
     */
    public CheckoutCreateRequestCurrency $currency;

    /**
     * Short unique identifier for the merchant that should receive the payment.
     *
     * @var string
     */
    public string $merchantCode;

    /**
     * Short merchant-defined description shown in SumUp tools and reporting for easier identification of the checkout.
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Optional backend callback URL for checkout status notifications. SumUp sends an HTTP POST with `event_type` and the checkout `id`. Retrieve the checkout to verify its current status before updating your order. See the [webhook guide](https://developer.sumup.com/online-payments/webhooks/) for the payload and response requirements.
     *
     * @var string|null
     */
    public ?string $returnUrl = null;

    /**
     * Merchant-scoped customer identifier. Required when setting up recurring payments and useful when the checkout should be linked to a returning payer.
     *
     * @var string|null
     */
    public ?string $customerId = null;

    /**
     * Business purpose of the checkout. Use `CHECKOUT` for a standard payment and `SETUP_RECURRING_PAYMENT` when collecting consent and payment details for future recurring charges.
     *
     * @var CheckoutCreateRequestPurpose|null
     */
    public ?CheckoutCreateRequestPurpose $purpose = null;

    /**
     * Optional expiration timestamp. The checkout must be processed before this moment, otherwise it becomes unusable. If omitted, the checkout does not have an explicit expiry time.
     *
     * @var string|null
     */
    public ?string $validUntil = null;

    /**
     * URL where the payer should be sent after a redirect-based payment or SCA flow completes. This is required for [APMs](https://developer.sumup.com/online-payments/apm/introduction) and recommended for card checkouts that may require [3DS](https://developer.sumup.com/online-payments/features/3ds). If it is omitted, the [Payment Widget](https://developer.sumup.com/online-payments/checkouts) can render the challenge in an iframe instead of using a full-page redirect.
     *
     * @var string|null
     */
    public ?string $redirectUrl = null;

    /**
     * Hosted Checkout configuration. Enable it to receive a SumUp-hosted payment page URL in the checkout response.
     *
     * @var HostedCheckout|null
     */
    public ?HostedCheckout $hostedCheckout = null;

    /**
     * Create request DTO.
     *
     * @param string $checkoutReference
     * @param float $amount
     * @param CheckoutCreateRequestCurrency|string $currency
     * @param string $merchantCode
     * @param string|null $description
     * @param string|null $returnUrl
     * @param string|null $customerId
     * @param CheckoutCreateRequestPurpose|string|null $purpose
     * @param string|null $validUntil
     * @param string|null $redirectUrl
     * @param HostedCheckout|null $hostedCheckout
     */
    public function __construct(
        string $checkoutReference,
        float $amount,
        CheckoutCreateRequestCurrency|string $currency,
        string $merchantCode,
        ?string $description = null,
        ?string $returnUrl = null,
        ?string $customerId = null,
        CheckoutCreateRequestPurpose|string|null $purpose = null,
        ?string $validUntil = null,
        ?string $redirectUrl = null,
        ?HostedCheckout $hostedCheckout = null
    ) {
        \SumUp\Hydrator::hydrate([
            'checkout_reference' => $checkoutReference,
            'amount' => $amount,
            'currency' => $currency,
            'merchant_code' => $merchantCode,
            'description' => $description,
            'return_url' => $returnUrl,
            'customer_id' => $customerId,
            'purpose' => $purpose,
            'valid_until' => $validUntil,
            'redirect_url' => $redirectUrl,
            'hosted_checkout' => $hostedCheckout,
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
            'checkout_reference' => 'checkoutReference',
            'amount' => 'amount',
            'currency' => 'currency',
            'merchant_code' => 'merchantCode',
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
