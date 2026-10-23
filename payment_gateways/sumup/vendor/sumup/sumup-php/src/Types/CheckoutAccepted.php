<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Response returned when checkout processing requires an additional payer action, such as a 3DS challenge or a redirect to an external payment method page.
 */
class CheckoutAccepted
{
    /**
     * Instructions for the next action the payer or client must take.
     *
     * @var CheckoutAcceptedNextStep|null
     */
    public ?CheckoutAcceptedNextStep $nextStep = null;

}
