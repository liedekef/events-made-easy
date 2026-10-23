<?php

declare(strict_types=1);

namespace SumUp\Types;

class Timestamps
{
    /**
     * The date and time when the resource was created. This is a string as defined in [RFC 3339, section 5.6](https://datatracker.ietf.org/doc/html/rfc3339#section-5.6).
     *
     * @var string
     */
    public string $createdAt;

    /**
     * The date and time when the resource was last updated. This is a string as defined in [RFC 3339, section 5.6](https://datatracker.ietf.org/doc/html/rfc3339#section-5.6).
     *
     * @var string
     */
    public string $updatedAt;

}
