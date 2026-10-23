<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Error payload with the invalid parameter reference.
 */
class ErrorExtended
{
    /**
     * Short description of the error.
     *
     * @var string|null
     */
    public ?string $message = null;

    /**
     * Platform code for the error.
     *
     * @var string|null
     */
    public ?string $errorCode = null;

    /**
     * Parameter name (with relative location) to which the error applies. Parameters from embedded resources are displayed using dot notation. For example, `card.name` refers to the `name` parameter embedded in the `card` object.
     *
     * @var string|null
     */
    public ?string $param = null;

}
