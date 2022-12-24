<?php

namespace ClassTransformer\DTO;

use ReflectionType;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;

use function sizeof;
use function in_array;
use function array_intersect;

/**
 * Class Property
 *
 * @author yzen.dev <yzen.dev@gmail.com>
 */
final class Property
{
    /**
     * @var ReflectionProperty
     */
    public ReflectionProperty $property;

    /**
     * @param ReflectionProperty $property
     */
    public function __construct(ReflectionProperty $property)
    {
        $this->property = $property;
    }

    /**
     * @return ReflectionType|null
     */
    public function getType(): ?ReflectionType
    {
        return $this->property->getType();
    }

    /**
     * @return array<string>
     */
    public function getTypes(): array
    {
        $types = [];
        $currentType = $this->getType();
        if ($currentType === null) {
            return [];
        }
        if ($currentType instanceof ReflectionUnionType) {
            $types = array_map(
                static function ($item) {
                    return $item->getName();
                },
                $currentType->getTypes()
            );
        }

        if ($currentType instanceof ReflectionNamedType) {
            $types = [$currentType->getName()];
        }

        if ($this->getType() !== null && $this->getType()->allowsNull()) {
            $types [] = 'null';
        }
        return $types;
    }

    /**
     * Finds whether a variable is a scalar
     *
     * @return bool
     */
    public function isScalar(): bool
    {
        return sizeof(array_intersect($this->getTypes(), ['int', 'float', 'double', 'string', 'bool', 'mixed'])) > 0;
    }

    /**
     * Finds whether a variable is an array
     * @return bool
     */
    public function isArray(): bool
    {
        return in_array('array', $this->getTypes(), true);
    }

    /**
     */
    public function getDocComment(): bool|string
    {
        return $this->property->getDocComment();
    }

    /**
     * @param string|null $name
     *
     * @return bool
     */
    public function existsAttribute(?string $name = null): bool
    {
        return $this->getAttributes($name) !== null;
    }

    /**
     * @param string|null $name
     *
     * @return null|array<mixed>
     */
    public function getAttributes(?string $name = null): ?array
    {
        $attr = $this->property->getAttributes($name);
        if (!empty($attr)) {
            return $attr;
        }
        return null;
    }
}
