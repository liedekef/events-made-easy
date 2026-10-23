<?php

namespace SumUp;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Hydrates SDK models from associative arrays or stdClass payloads.
 */
class Hydrator
{
    /**
     * Cached reflection data for hydrated classes.
     *
     * @var array<string, array<string, ReflectionProperty>>
     */
    private static $propertyCache = [];

    /**
     * Hydrate the provided payload into the given class.
     *
     * @param mixed $payload
     * @param string $className
     * @param object|null $target Existing instance to hydrate.
     *
     * @return mixed
     */
    public static function hydrate($payload, $className, $target = null)
    {
        if ($payload === null || $className === '' || !class_exists($className)) {
            return $payload;
        }

        if ($payload instanceof $className) {
            return $payload;
        }

        if (!is_array($payload)) {
            return $payload;
        }

        $object = ($target instanceof $className)
            ? $target
            : (new ReflectionClass($className))->newInstanceWithoutConstructor();
        $properties = self::getClassProperties($className);

        foreach ($payload as $key => $value) {
            $propertyName = self::normalizePropertyName($key);
            if (!isset($properties[$propertyName])) {
                continue;
            }

            $property = $properties[$propertyName];
            $property->setValue($object, self::castValue($value, $property));
        }

        return $object;
    }

    /**
     * @param string $className
     *
     * @return array<string, ReflectionProperty>
     */
    private static function getClassProperties($className)
    {
        if (isset(self::$propertyCache[$className])) {
            return self::$propertyCache[$className];
        }

        if (!class_exists($className)) {
            return [];
        }

        $refClass = new ReflectionClass($className);
        $properties = [];
        foreach ($refClass->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[$property->getName()] = $property;
        }

        self::$propertyCache[$className] = $properties;

        return $properties;
    }

    /**
     * @param mixed $value
     * @param ReflectionProperty $property
     *
     * @return mixed
     */
    private static function castValue($value, ReflectionProperty $property)
    {
        if ($value === null) {
            return null;
        }

        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();
        if ($type->isBuiltin()) {
            switch ($typeName) {
                case 'int':
                    return (int) $value;
                case 'float':
                    return (float) $value;
                case 'bool':
                    return (bool) $value;
                case 'string':
                    return (string) $value;
                case 'array':
                    return self::castArrayValue($value, $property);
                default:
                    return $value;
            }
        }

        if (enum_exists($typeName)) {
            return self::castEnumValue($value, $typeName);
        }

        return self::hydrate($value, $typeName);
    }

    /**
     * @param mixed $value
     * @param string $enumClass
     *
     * @return mixed
     */
    private static function castEnumValue($value, $enumClass)
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (method_exists($enumClass, 'tryFrom')) {
            $enum = $enumClass::tryFrom($value);
            if ($enum !== null) {
                return $enum;
            }
        }

        if (method_exists($enumClass, 'from')) {
            return $enumClass::from($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @param ReflectionProperty $property
     *
     * @return array<int|string, mixed>
     */
    private static function castArrayValue($value, ReflectionProperty $property)
    {
        if (!is_array($value)) {
            return [];
        }

        $itemType = self::extractArrayItemType($property);
        if ($itemType === null) {
            return $value;
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = self::castArrayItem($item, $itemType, $property);
        }

        return $result;
    }

    /**
     * @param ReflectionProperty $property
     *
     * @return string|null
     */
    private static function extractArrayItemType(ReflectionProperty $property)
    {
        $docComment = $property->getDocComment();
        if ($docComment === false) {
            return null;
        }

        if (!preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
            return null;
        }

        $types = explode('|', $matches[1]);
        foreach ($types as $type) {
            $type = trim($type);
            if ($type === '' || $type === 'null') {
                continue;
            }
            if (substr($type, -2) === '[]') {
                return substr($type, 0, -2);
            }
        }

        return null;
    }

    /**
     * @param mixed $item
     * @param string $itemType
     * @param ReflectionProperty $property
     *
     * @return mixed
     */
    private static function castArrayItem($item, $itemType, ReflectionProperty $property)
    {
        $normalizedType = ltrim($itemType, '\\');
        switch ($normalizedType) {
            case 'string':
                return (string) $item;
            case 'int':
                return (int) $item;
            case 'float':
                return (float) $item;
            case 'bool':
                return (bool) $item;
            case 'array':
                return is_array($item) ? $item : [];
            case 'mixed':
                return $item;
        }

        $className = $itemType;
        if ($itemType[0] !== '\\') {
            $namespace = $property->getDeclaringClass()->getNamespaceName();
            if (!empty($namespace)) {
                $className = $namespace . '\\' . $itemType;
            } else {
                $className = $itemType;
            }
        } else {
            $className = ltrim($itemType, '\\');
        }

        if (!class_exists($className)) {
            return $item;
        }

        return self::hydrate($item, $className);
    }

    /**
     * Normalize serialized property names into PHP property names.
     *
     * @param string $name
     *
     * @return string
     */
    private static function normalizePropertyName($name)
    {
        $value = trim((string) $name);
        $value = str_replace('[]', 'List', $value);
        $value = str_replace(['.', '-', ' '], '_', $value);
        if ($value === '') {
            $value = 'field';
        }

        $value = self::toLowerCamel($value);
        if (self::isReservedWord($value)) {
            $value .= 'Value';
        }

        return $value;
    }

    /**
     * Convert strings to lower camelCase.
     *
     * @param string $value
     *
     * @return string
     */
    private static function toLowerCamel($value)
    {
        $value = str_replace('_', ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return lcfirst($value);
    }

    /**
     * @param string $word
     *
     * @return bool
     */
    private static function isReservedWord($word)
    {
        static $reserved = [
            'abstract' => true,
            'array' => true,
            'callable' => true,
            'class' => true,
            'const' => true,
            'default' => true,
            'function' => true,
            'global' => true,
            'interface' => true,
            'new' => true,
            'private' => true,
            'protected' => true,
            'public' => true,
            'static' => true,
            'string' => true,
            'int' => true,
            'float' => true,
            'bool' => true,
            'self' => true,
            'parent' => true,
            'trait' => true,
            'namespace' => true,
        ];

        return isset($reserved[$word]);
    }
}
