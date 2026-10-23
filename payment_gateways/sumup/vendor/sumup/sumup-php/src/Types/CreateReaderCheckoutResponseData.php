<?php

declare(strict_types=1);

namespace SumUp\Types;

class CreateReaderCheckoutResponseData
{
    /**
     * The checkout ID is a unique identifier for the checkout.
     *
     * @var string|null
     */
    public ?string $checkoutId = null;

    /**
     * The client transaction ID is a unique identifier for the transaction that is generated for the client.
     * It can be used later to fetch the transaction details via the [Transactions API](https://developer.sumup.com/api/transactions/get).
     *
     * @var string
     */
    public string $clientTransactionId;

}
