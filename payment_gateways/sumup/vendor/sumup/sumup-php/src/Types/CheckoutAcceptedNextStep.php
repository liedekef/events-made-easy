<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Instructions for the next action the payer or client must take.
 */
class CheckoutAcceptedNextStep
{
    /**
     * URL to open or submit in order to continue processing.
     *
     * @var string|null
     */
    public ?string $url = null;

    /**
     * HTTP method to use when following the next step.
     *
     * @var string|null
     */
    public ?string $method = null;

    /**
     * Merchant URL where the payer returns after the external flow finishes.
     *
     * @var string|null
     */
    public ?string $redirectUrl = null;

    /**
     * Allowed presentation mechanisms for the next step. `iframe` means the flow can be embedded, while `browser` means it can be completed through a full-page redirect.
     *
     * @var string[]|null
     */
    public ?array $mechanism = null;

    /**
     * Parameters required to complete the next step. The exact keys depend on the payment provider and flow type.
     *
     * @var array<string, mixed>|null
     */
    public ?array $payload = null;

}
