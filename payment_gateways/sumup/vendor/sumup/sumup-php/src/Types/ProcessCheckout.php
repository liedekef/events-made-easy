<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Request body for attempting payment on an existing checkout. The required companion fields depend on the selected `payment_type`, for example card details, saved-card data, or payer information required by a specific payment method.
 */
class ProcessCheckout
{
    /**
     * Payment method used for this processing attempt. It determines which additional request fields are required.
     *
     * @var ProcessCheckoutPaymentType
     */
    public ProcessCheckoutPaymentType $paymentType;

    /**
     * Number of installments for deferred payments. Available only to merchant users in Brazil.
     *
     * @var int|null
     */
    public ?int $installments = null;

    /**
     * Mandate details used when a checkout should create a reusable card token for future recurring or merchant-initiated payments.
     *
     * @var MandatePayload|null
     */
    public ?MandatePayload $mandate = null;

    /**
     * __Required when payment type is `card`.__ Details of the payment card.
     *
     * @var Card|null
     */
    public ?Card $card = null;

    /**
     * Raw `PaymentData` object received from Google Pay. Send the Google Pay response payload as-is.
     *
     * @var array<string, mixed>|null
     */
    public ?array $googlePay = null;

    /**
     * Raw payment token object received from Apple Pay. Send the Apple Pay response payload as-is.
     *
     * @var array<string, mixed>|null
     */
    public ?array $applePay = null;

    /**
     * Token of a saved payment instrument returned by checkout processing or the customer's payment-instruments endpoint. To charge a saved card, set `payment_type` to `card` and provide both this `token` and the associated `customer_id` instead of raw card details.
     *
     * @var string|null
     */
    public ?string $token = null;

    /**
     * Customer identifier associated with the saved payment instrument. Required when `token` is provided.
     *
     * @var string|null
     */
    public ?string $customerId = null;

    /**
     * Personal details for the customer.
     *
     * @var PersonalDetails|null
     */
    public ?PersonalDetails $personalDetails = null;

    /**
     * Create request DTO.
     *
     * @param ProcessCheckoutPaymentType|string $paymentType
     * @param int|null $installments
     * @param MandatePayload|null $mandate
     * @param Card|null $card
     * @param array<string, mixed>|null $googlePay
     * @param array<string, mixed>|null $applePay
     * @param string|null $token
     * @param string|null $customerId
     * @param PersonalDetails|null $personalDetails
     */
    public function __construct(
        ProcessCheckoutPaymentType|string $paymentType,
        ?int $installments = null,
        ?MandatePayload $mandate = null,
        ?Card $card = null,
        ?array $googlePay = null,
        ?array $applePay = null,
        ?string $token = null,
        ?string $customerId = null,
        ?PersonalDetails $personalDetails = null
    ) {
        \SumUp\Hydrator::hydrate([
            'payment_type' => $paymentType,
            'installments' => $installments,
            'mandate' => $mandate,
            'card' => $card,
            'google_pay' => $googlePay,
            'apple_pay' => $applePay,
            'token' => $token,
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
            'payment_type' => 'paymentType',
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
