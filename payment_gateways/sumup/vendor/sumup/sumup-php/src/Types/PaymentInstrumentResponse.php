<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of a saved payment instrument.
 */
class PaymentInstrumentResponse
{
    /**
     * Token identifying the customer's saved payment card. Pass it as `token`, together with the associated `customer_id` and `payment_type = card`, when processing a checkout with this instrument.
     *
     * @var string|null
     */
    public ?string $token = null;

    /**
     * Indicates whether the payment instrument is active and can be used for payments. To deactivate it, send a `DELETE` request to the resource endpoint.
     *
     * @var bool|null
     */
    public ?bool $active = null;

    /**
     * Type of the payment instrument.
     *
     * @var PaymentInstrumentResponseType|null
     */
    public ?PaymentInstrumentResponseType $type = null;

    /**
     * Details of the payment card.
     *
     * @var PaymentInstrumentResponseCard|null
     */
    public ?PaymentInstrumentResponseCard $card = null;

    /**
     * Details of the mandate linked to the saved payment instrument.
     *
     * @var MandateResponse|null
     */
    public ?MandateResponse $mandate = null;

    /**
     * The timestamp of when the payment instrument was created.
     *
     * @var string|null
     */
    public ?string $createdAt = null;

}
