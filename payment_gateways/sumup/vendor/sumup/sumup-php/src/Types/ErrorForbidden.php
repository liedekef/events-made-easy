<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of an error returned for a forbidden request.
 */
class ErrorForbidden
{
    /**
     * Short description of the error.
     *
     * @var string|null
     */
    public ?string $errorMessage = null;

    /**
     * Platform code for the error.
     *
     * @var string|null
     */
    public ?string $errorCode = null;

    /**
     * HTTP status code for the error.
     *
     * @var string|null
     */
    public ?string $statusCode = null;

}
