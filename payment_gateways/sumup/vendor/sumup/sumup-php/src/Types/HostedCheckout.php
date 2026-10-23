<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Hosted Checkout configuration. Enable it to receive a SumUp-hosted payment page URL in the checkout response.
 */
class HostedCheckout
{
    /**
     * Whether the checkout should include a SumUp-hosted payment page.
     *
     * @var bool
     */
    public bool $enabled;

}
