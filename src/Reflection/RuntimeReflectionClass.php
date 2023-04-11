<?php

namespace ClassTransformer\Reflection;

use ClassTransformer\Contracts\ReflectionClass;
use ClassTransformer\Contracts\ClassTransformable;
use ClassTransformer\Validators\ClassExistsValidator;
use ClassTransformer\Exceptions\ClassNotFoundException;
use ReflectionClass as PhpReflectionClass;

/**
 * Class RuntimeReflectionClass
 *
 * @psalm-api
 * @template T of ClassTransformable
 */
final class RuntimeReflectionClass implements ReflectionClass
{
    /** @var class-string<T> $class */
    private string $class;

    /**
     * @var array<string,\ReflectionProperty[]>
     */
    private static $propertiesTypesCache = [];


    /**
     * @param class-string<T> $class
     *
     * @throws ClassNotFoundException
     */
    public function __construct(string $class)
    {
        new ClassExistsValidator($class);

        $this->class = $class;
    }

    /**
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    public function getProperties(): array
    {
        if (isset(static::$propertiesTypesCache[$this->class])) {
            return static::$propertiesTypesCache[$this->class];
        }

        $refInstance = new PhpReflectionClass($this->class);

        $properties = $refInstance->getProperties();
        $result = [];
        foreach ($properties as $item) {
            $result [] = new RuntimeReflectionProperty($item);
        }
        
        return static::$propertiesTypesCache[$this->class] = $result;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }
}