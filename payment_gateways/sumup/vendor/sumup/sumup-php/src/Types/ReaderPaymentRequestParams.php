<?php

declare(strict_types=1);

namespace SumUp\Types;

class ReaderPaymentRequestParams
{
    /**
     *
     * @var mixed|null
     */
    public mixed $affiliate = null;

    /**
     * Caller-supplied correlation identifier, used as the idempotency key.
     *
     * @var string
     */
    public string $clientTransactionId;

    /**
     * Portion of total_amount that is a tip, in minor units.
     *
     * @var int|null
     */
    public ?int $tipAmount = null;

    /**
     *
     * @var mixed
     */
    public mixed $totalAmount;

    /**
     * Create request DTO.
     *
     * @param string $clientTransactionId
     * @param mixed $totalAmount
     * @param mixed|null $affiliate
     * @param int|null $tipAmount
     */
    public function __construct(
        string $clientTransactionId,
        mixed $totalAmount,
        mixed $affiliate = null,
        ?int $tipAmount = null
    ) {
        \SumUp\Hydrator::hydrate([
            'client_transaction_id' => $clientTransactionId,
            'total_amount' => $totalAmount,
            'affiliate' => $affiliate,
            'tip_amount' => $tipAmount,
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
            'client_transaction_id' => 'clientTransactionId',
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
