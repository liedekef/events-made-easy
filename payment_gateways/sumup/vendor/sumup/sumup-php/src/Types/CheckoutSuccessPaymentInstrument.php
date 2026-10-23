<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the saved payment instrument created or reused during checkout processing.
 */
class CheckoutSuccessPaymentInstrument
{
    /**
     * Unique token of the saved payment instrument.
     *
     * @var string|null
     */
    public ?string $token = null;

}
