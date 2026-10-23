<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Information about the underlying physical device.
 */
class ReaderDevice
{
    /**
     * A unique identifier of the physical device (e.g. serial number).
     *
     * @var string
     */
    public string $identifier;

    /**
     * Identifier of the model of the device.
     *
     * @var ReaderDeviceModel
     */
    public ReaderDeviceModel $model;

}
