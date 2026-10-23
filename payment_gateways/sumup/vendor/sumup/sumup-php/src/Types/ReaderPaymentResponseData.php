<?php

declare(strict_types=1);

namespace SumUp\Types;

class ReaderPaymentResponseData
{
    /**
     * Caller-supplied correlation identifier that was provided in the request.
     *
     * @var string|null
     */
    public ?string $clientTransactionId = null;

    /**
     * Transaction code returned by the acquirer/processing entity after processing the transaction.
     *
     * @var string|null
     */
    public ?string $transactionCode = null;

}
