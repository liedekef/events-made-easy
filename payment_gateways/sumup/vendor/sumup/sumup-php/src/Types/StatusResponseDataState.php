<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Latest state of the device
 */
enum StatusResponseDataState: string
{
    case IDLE = 'IDLE';
    case SELECTING_TIP = 'SELECTING_TIP';
    case WAITING_FOR_CARD = 'WAITING_FOR_CARD';
    case WAITING_FOR_PIN = 'WAITING_FOR_PIN';
    case WAITING_FOR_SIGNATURE = 'WAITING_FOR_SIGNATURE';
    case UPDATING_FIRMWARE = 'UPDATING_FIRMWARE';
}
