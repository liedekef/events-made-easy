<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Optional object containing data for transactions from ERP integrators in Greece that comply with the AADE 1155 protocol.
 * When such regulatory/business requirements apply, this object must be provided and contains the data needed to validate the transaction with the AADE signature provider.
 *
 */
class CreateReaderCheckoutRequestAade
{
    /**
     * The identifier of the AADE signature provider.
     *
     * @var string
     */
    public string $providerId;

    /**
     * The base64 encoded signature of the transaction data.
     *
     * @var string
     */
    public string $signature;

    /**
     * The string containing the signed transaction data.
     *
     * @var string
     */
    public string $signatureData;

}
