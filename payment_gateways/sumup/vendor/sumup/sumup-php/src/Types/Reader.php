<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * A physical card reader device that can accept in-person payments.
 */
class Reader
{
    /**
     * Unique identifier of the reader that the payment is initiated on.
     *
     * @var string
     */
    public string $id;

    /**
     * Custom human-readable, user-defined name for easier identification of the reader.
     *
     * @var string
     */
    public string $name;

    /**
     * The status of the reader object gives information about the current state of the reader.
     * Possible values:
     * - `unknown` - The reader status is unknown.
     * - `processing` - The reader is created and waits for the physical device to confirm the pairing.
     * - `paired` - The reader is paired with a merchant account and can be used with SumUp APIs.
     * - `expired` - The pairing is expired and no longer usable with the account. The resource needs to get recreated.
     *
     * @var ReaderStatus
     */
    public ReaderStatus $status;

    /**
     * Information about the underlying physical device.
     *
     * @var ReaderDevice
     */
    public ReaderDevice $device;

    /**
     * Set of user-defined key-value pairs attached to the object. Partial updates are not supported. When updating, always submit whole metadata. Maximum of 64 parameters are allowed in the object.
     *
     * @var array<string, mixed>|null
     */
    public ?array $metadata = null;

    /**
     * Identifier of the system-managed service account associated with this reader.
     * Present only for readers that are already paired.
     * This field is currently in beta and may change.
     *
     * @var string|null
     */
    public ?string $serviceAccountId = null;

    /**
     * The timestamp of when the reader was created.
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The timestamp of when the reader was last updated.
     *
     * @var string
     */
    public string $updatedAt;

}
