<?php

namespace ClassTransformer;

use function ucwords;
use function lcfirst;
use function is_string;
use function strtolower;
use function preg_match;
use function str_replace;
use function preg_replace;
use function array_key_exists;

/**
 *
 */
final class TransformUtils
{
    /** @var array<string> */
    private static array $camelCache = [];

    /** @var array<string> */
    private static array $snakeCache = [];

    /** @var array<string> */
    private static array $mutationSetterCache = [];

    /**
     * @param string $key
     *
     * @return string
     */
    public static function attributeToSnakeCase(string $key): string
    {
        if (isset(static::$snakeCache[$key])) {
            return static::$snakeCache[$key];
        }
        $str = preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '_', $key) ?? '';
        return static::$snakeCache[$key] = strtolower($str);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function attributeToCamelCase(string $key): string
    {
        if (isset(static::$camelCache[$key])) {
            return static::$camelCache[$key];
        }
        $str = lcfirst(str_replace('_', '', ucwords($key, '_')));
        return static::$camelCache[$key] = $str;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function mutationSetterToCamelCase(string $key): string
    {
        if (isset(static::$mutationSetterCache[$key])) {
            return static::$mutationSetterCache[$key];
        }
        $str = 'set' . ucfirst(self::attributeToCamelCase($key)) . 'Attribute';
        return static::$mutationSetterCache[$key] = $str;
    }

    /**
     * @param string|bool $phpDoc
     *
     * @return string|null
     */
    public static function getClassFromPhpDoc($phpDoc): ?string
    {
        if (is_string($phpDoc)) {
            preg_match('/array<([a-zA-Z\d\\\]+)>/m', $phpDoc, $arrayType);
            return $arrayType[1] ?? null;
        }
        return null;
    }
}