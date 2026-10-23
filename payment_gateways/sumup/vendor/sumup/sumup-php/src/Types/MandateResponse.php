<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the mandate linked to the saved payment instrument.
 */
class MandateResponse
{
    /**
     * Type of mandate stored for the checkout or payment instrument.
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Current lifecycle status of the mandate.
     *
     * @var MandateResponseStatus|null
     */
    public ?MandateResponseStatus $status = null;

    /**
     * Short unique identifier for the merchant for which the mandate is valid.
     *
     * @var string|null
     */
    public ?string $merchantCode = null;

}
