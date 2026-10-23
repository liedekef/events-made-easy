<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of a link to a related resource.
 */
class Link
{
    /**
     * Relation of the linked resource to the current resource.
     *
     * @var string|null
     */
    public ?string $rel = null;

    /**
     * URL for accessing the related resource.
     *
     * @var string|null
     */
    public ?string $href = null;

    /**
     * Media type of the linked resource.
     *
     * @var string|null
     */
    public ?string $type = null;

    /**
     * Minimum amount allowed for a refund, in major units.
     *
     * @var float|null
     */
    public ?float $minAmount = null;

    /**
     * Maximum amount allowed for a refund, in major units.
     *
     * @var float|null
     */
    public ?float $maxAmount = null;

}
