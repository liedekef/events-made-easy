<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Details of the device used to create the transaction.
 */
class Device
{
    /**
     * Device name.
     *
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Device OS.
     *
     * @var string|null
     */
    public ?string $systemName = null;

    /**
     * Device model.
     *
     * @var string|null
     */
    public ?string $model = null;

    /**
     * Device OS version.
     *
     * @var string|null
     */
    public ?string $systemVersion = null;

    /**
     * Device UUID.
     *
     * @var string|null
     */
    public ?string $uuid = null;

}
