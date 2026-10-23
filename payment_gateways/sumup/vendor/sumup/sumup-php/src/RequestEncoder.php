<?php

namespace SumUp;

/**
 * Encodes request DTO objects into payload arrays.
 */
class RequestEncoder
{
    /**
     * @param mixed $value
     *
     * @return array<int|string, mixed>
     */
    public static function encode($value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $normalized = self::normalize($value);
        if (is_array($normalized)) {
            return $normalized;
        }

        return [];
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function normalize($value)
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = self::normalize($item);
            }

            return $result;
        }

        if (!is_object($value)) {
            return $value;
        }

        $result = [];
        foreach (get_object_vars($value) as $key => $item) {
            if ($item === null) {
                continue;
            }
            $result[self::toSnakeCase((string) $key)] = self::normalize($item);
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private static function toSnakeCase($value): string
    {
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);
        if (!is_string($snake) || $snake === '') {
            return $value;
        }

        return strtolower($snake);
    }
}
