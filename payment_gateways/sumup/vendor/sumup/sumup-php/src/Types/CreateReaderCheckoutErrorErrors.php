<?php

declare(strict_types=1);

namespace SumUp\Types;

class CreateReaderCheckoutErrorErrors
{
    /**
     * Error message
     *
     * @var string|null
     */
    public ?string $detail = null;

    /**
     * Error code
     *
     * @var string
     */
    public string $type;

}
