<?php

namespace ClassTransformer;

use ReflectionType;
use ReflectionProperty;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionUnionType;
use ClassTransformer\Attributes\NotTransform;

use function sizeof;
use function in_array;
use function array_intersect;

/**
 * Class GenericProperty
 *
 * @author yzen.dev <yzen.dev@gmail.com>
 */
final class GenericProperty
{
    /**
     * @var ReflectionProperty
     */
    public ReflectionProperty $property;

    readonly public ?ReflectionType $type;
    readonly public array $types;

    /** @var class-string|string $propertyClass */
    readonly public string $name;
    readonly public bool $isScalar;

    /**
     * @param ReflectionProperty $property
     */
    public function __construct(ReflectionProperty $property)
    {
        $this->property = $property;
        $this->type = $this->property->getType();
        $this->name = $this->property->name;
        $this->types = $this->initTypes();
        $this->isScalar = sizeof(array_intersect($this->types, ['int', 'float', 'double', 'string', 'bool', 'mixed'])) > 0;
    }

    /**
     * @return bool
     */
    public function isEnum(): bool
    {
        return enum_exists($this->type->getName());
    }

    /**
     * @return array<string>
     */
    private function initTypes(): array
    {
        $types = [];

        if ($this->type === null) {
            return [];
        }
        
        if ($this->type instanceof ReflectionUnionType) {
            $types = array_map(
                static function ($item) {
                    return $item->getName();
                },
                $this->type->getTypes()
            );
        }

        if ($this->type instanceof ReflectionNamedType) {
            $types = [$this->type->getName()];
        }

        if ($this->type !== null && $this->type->allowsNull()) {
            $types [] = 'null';
        }
        return $types;
    }

    /**
     * Finds whether a variable is an array
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return in_array('array', $this->types, true);
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
     * @return bool
     */
    public function notTransform(): bool
    {
        return $this->notTransform = $this->existsAttribute(NotTransform::class);
    }

    /**
     * @param string|null $name
     *
     * @return null|ReflectionAttribute[]
     */
    public function getAttributes(?string $name = null): ?array
    {
        $attr = $this->property->getAttributes($name);
        if (!empty($attr)) {
            return $attr;
        }
        return null;
    }
    
    /**
     * @param string|null $name
     *
     * @return null|ReflectionAttribute
     */
    public function getAttribute(?string $name = null): ?ReflectionAttribute
    {
        $attr = $this->property->getAttributes($name);
        if (!empty($attr)) {
            return $attr[0];
        }
        return null;
    }
}
