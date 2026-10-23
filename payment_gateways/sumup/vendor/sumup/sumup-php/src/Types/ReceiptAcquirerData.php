<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Acquirer-specific metadata related to the card authorization.
 */
class ReceiptAcquirerData
{
    /**
     * Identifier of the terminal used for the authorization.
     *
     * @var string|null
     */
    public ?string $tid = null;

    /**
     * Authorization code returned by the acquirer.
     *
     * @var string|null
     */
    public ?string $authorizationCode = null;

    /**
     * Return code reported by the acquirer.
     *
     * @var string|null
     */
    public ?string $returnCode = null;

    /**
     * Local timestamp of the card authorization.
     *
     * @var string|null
     */
    public ?string $localTime = null;

}
