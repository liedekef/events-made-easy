<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Reader Checkout
 */
class CreateReaderCheckoutRequest
{
    /**
     * Optional object containing data for transactions from ERP integrators in Greece that comply with the AADE 1155 protocol.
     * When such regulatory/business requirements apply, this object must be provided and contains the data needed to validate the transaction with the AADE signature provider.
     *
     * @var CreateReaderCheckoutRequestAade|null
     */
    public ?CreateReaderCheckoutRequestAade $aade = null;

    /**
     * Affiliate metadata for the transaction.
     * It is a field that allow for integrators to track the source of the transaction.
     *
     * @var CreateReaderCheckoutRequestAffiliate|null
     */
    public ?CreateReaderCheckoutRequestAffiliate $affiliate = null;

    /**
     * The card type of the card used for the transaction.
     * Is is required only for some countries (e.g: Brazil).
     *
     * @var CreateReaderCheckoutRequestCardType|null
     */
    public ?CreateReaderCheckoutRequestCardType $cardType = null;

    /**
     * Description of the checkout to be shown in the Merchant Sales
     *
     * @var string|null
     */
    public ?string $description = null;

    /**
     * Number of installments for the transaction.
     * It may vary according to the merchant country.
     * For example, in Brazil, the maximum number of installments is 12.
     * Omit if the merchant country does support installments.
     * Otherwise, the checkout will be rejected.
     *
     * @var int|null
     */
    public ?int $installments = null;

    /**
     * Webhook URL to which the payment result will be sent.
     * It must be a HTTPS url.
     *
     * @var string|null
     */
    public ?string $returnUrl = null;

    /**
     * List of tipping rates to be displayed to the cardholder.
     * The rates are in percentage and should be between 0.01 and 0.99.
     * The list should be sorted in ascending order.
     *
     * @var float[]|null
     */
    public ?array $tipRates = null;

    /**
     * Time in seconds the cardholder has to select a tip rate.
     * If not provided, the default value is 30 seconds.
     * It can only be set if `tip_rates` is provided.
     * **Note**: If the target device is a Solo, it must be in version 3.3.38.0 or higher.
     *
     * @var int|null
     */
    public ?int $tipTimeout = null;

    /**
     * Amount structure.
     * The amount is represented as an integer value altogether with the currency and the minor unit.
     * For example, EUR 1.00 is represented as value 100 with minor unit of 2.
     *
     * @var CreateReaderCheckoutRequestTotalAmount
     */
    public CreateReaderCheckoutRequestTotalAmount $totalAmount;

    /**
     * Create request DTO.
     *
     * @param CreateReaderCheckoutRequestTotalAmount $totalAmount
     * @param CreateReaderCheckoutRequestAade|null $aade
     * @param CreateReaderCheckoutRequestAffiliate|null $affiliate
     * @param CreateReaderCheckoutRequestCardType|string|null $cardType
     * @param string|null $description
     * @param int|null $installments
     * @param string|null $returnUrl
     * @param float[]|null $tipRates
     * @param int|null $tipTimeout
     */
    public function __construct(
        CreateReaderCheckoutRequestTotalAmount $totalAmount,
        ?CreateReaderCheckoutRequestAade $aade = null,
        ?CreateReaderCheckoutRequestAffiliate $affiliate = null,
        CreateReaderCheckoutRequestCardType|string|null $cardType = null,
        ?string $description = null,
        ?int $installments = null,
        ?string $returnUrl = null,
        ?array $tipRates = null,
        ?int $tipTimeout = null
    ) {
        \SumUp\Hydrator::hydrate([
            'total_amount' => $totalAmount,
            'aade' => $aade,
            'affiliate' => $affiliate,
            'card_type' => $cardType,
            'description' => $description,
            'installments' => $installments,
            'return_url' => $returnUrl,
            'tip_rates' => $tipRates,
            'tip_timeout' => $tipTimeout,
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
            'total_amount' => 'totalAmount',
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
