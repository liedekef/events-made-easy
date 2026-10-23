<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Type of connection used by the device
 */
enum StatusResponseDataConnectionType: string
{
    case BTLE = 'btle';
    case EDGE = 'edge';
    case GPRS = 'gprs';
    case LTE = 'lte';
    case UMTS = 'umts';
    case USB = 'usb';
    case WI_FI = 'Wi-Fi';
}
