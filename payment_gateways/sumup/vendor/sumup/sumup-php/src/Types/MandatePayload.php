<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Mandate details used when a checkout should create a reusable card token for future recurring or merchant-initiated payments.
 */
class MandatePayload
{
    /**
     * Type of mandate to create for the saved payment instrument.
     *
     * @var MandatePayloadType
     */
    public MandatePayloadType $type;

    /**
     * Browser or client user agent observed when consent was collected.
     *
     * @var string
     */
    public string $userAgent;

    /**
     * IP address of the payer when the mandate was accepted.
     *
     * @var string|null
     */
    public ?string $userIp = null;

}
