<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of a request validation error.
 */
class DetailsError
{
    /**
     * Short title of the error.
     *
     * @var string|null
     */
    public ?string $title = null;

    /**
     * Details of the error.
     *
     * @var string|null
     */
    public ?string $details = null;

    /**
     * HTTP status code for the error.
     *
     * @var float|null
     */
    public ?float $status = null;

    /**
     * List of violated validation constraints.
     *
     * @var array<string, mixed>[]|null
     */
    public ?array $failedConstraints = null;

}
