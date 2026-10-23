<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Identifier of the model of the device.
 */
enum ReaderDeviceModel: string
{
    case SOLO = 'solo';
    case VIRTUAL_SOLO = 'virtual-solo';
}
