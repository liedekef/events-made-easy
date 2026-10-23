<?php

declare(strict_types=1);

namespace SumUp\Types;

class UnauthorizedErrors
{
    /**
     * Fuller message giving context to error
     *
     * @var string
     */
    public string $detail;

    /**
     * Key indicating type of error. Present only for typed 401 responses (e.g. invalid token, invalid password). Absent for generic unauthorized responses.
     *
     * @var UnauthorizedErrorsType|null
     */
    public ?UnauthorizedErrorsType $type = null;

}
