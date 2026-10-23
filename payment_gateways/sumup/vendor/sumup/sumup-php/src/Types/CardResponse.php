<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the payment card.
 */
class CardResponse
{
    /**
     * Last 4 digits of the payment card number.
     *
     * @var string|null
     */
    public ?string $last4Digits = null;

    /**
     * Issuing card network of the payment card used for the transaction.
     *
     * @var CardResponseType|null
     */
    public ?CardResponseType $type = null;

    /**
     * Payment Account Reference (PAR) defined by [EMVCo](https://www.emvco.com/emv-technologies/payment-tokenisation/). It links a card's primary account number (PAN) with its affiliated payment tokens, allowing transactions made with the physical card and tokenized versions of that card, such as digital wallets, to be correlated when PAR is available.
     * This reference cannot be used to initiate a payment and is separate from the saved payment instrument `token` used to process checkouts. Returned only when available for the card; integrations must handle its absence.
     *
     * @var string|null
     */
    public ?string $paymentAccountReference = null;

}
