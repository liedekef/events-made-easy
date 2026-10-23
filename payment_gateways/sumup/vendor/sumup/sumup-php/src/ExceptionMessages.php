<?php

namespace SumUp;

/**
 * Class ExceptionMessages
 *
 * @package SumUp
 */
class ExceptionMessages
{
    /**
     * Get formatted message for missing parameter.
     *
     * @param string $missingParamName
     *
     * @return string
     */
    public static function getMissingParamMsg(string $missingParamName): string
    {
        return 'Missing parameter: "' . $missingParamName . '".';
    }
}
