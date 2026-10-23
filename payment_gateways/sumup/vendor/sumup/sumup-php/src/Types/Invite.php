<?php

declare(strict_types=1);

namespace SumUp\Types;

/**
 * Pending invitation for membership.
 */
class Invite
{
    /**
     * Email address of the invited user.
     *
     * @var string
     */
    public string $email;

    /**
     *
     * @var string
     */
    public string $expiresAt;

}
