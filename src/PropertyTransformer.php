<?php

namespace ClassTransformer;

use ClassTransformer\Attributes\ConvertArray;
use ClassTransformer\Attributes\WritingStyle;
use ClassTransformer\DTO\Property;
use ClassTransformer\Exceptions\ClassNotFoundException;
use ClassTransformer\Exceptions\InvalidArgumentException;
use ClassTransformer\Exceptions\ValueNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Class ClassTransformerService
 *
 * @author yzen.dev <yzen.dev@gmail.com>
 */
class PropertyTransformer
{
    /**
     * @template T
     * class-string<T> $className
     */
    private string $className;

    /** @var array<mixed> */
    private array $args;

    /** @var bool Arguments transfer as named arguments (for php8) */
    private $isNamedArguments;

    /**
     * @template T
     *
     * @param string|class-string<T> $className
     * @param array<mixed>|object|null $args
     *
     * @throws ClassNotFoundException
     */
    public function __construct(string $className, ...$args)
    {
        $this->className = $className;
        $this->validate();

        $this->isNamedArguments = count(func_get_args()) === 1;

        // if dynamic arguments, named ones lie immediately in the root, if they were passed as an array, then they need to be unpacked
        $inArgs = $this->isNamedArguments ? $args : $args[0];

        if (is_object($inArgs)) {
            $inArgs = (array)$inArgs;
        }
        $this->args = $inArgs ?? [];
    }

    /**
     * @template T
     *
     * @param string|class-string<T> $className
     * @param array<mixed>|object|null $args
     *
     * @return PropertyTransformer
     * @throws ClassNotFoundException
     */
    public static function init(string $className, ...$args): PropertyTransformer
    {
        return new self($className, ...$args);
    }

    /**
     * @return void
     * @throws ClassNotFoundException
     */
    private function validate()
    {
        if (!class_exists($this->className)) {
            throw new ClassNotFoundException("Class $this->className not found. Please check the class path you specified.");
        }
    }

    /**
     * @template T
     *
     * @return T
     * @throws ClassNotFoundException|ReflectionException
     */
    public function transform()
    {
        /** @phpstan-ignore-next-line */
        $refInstance = new ReflectionClass($this->className);

        // if exist custom transform method
        if (method_exists($this->className, 'transform')) {
            /** @phpstan-ignore-next-line */
            return $this->className::transform($this->args);
        }

        /** @var T $instance */
        $instance = new $this->className();
        foreach ($refInstance->getProperties() as $item) {
            $property = new Property($refInstance->getProperty($item->name));

            try {
                $value = $this->searchValue($item);
            } catch (ValueNotFoundException) {
                continue;
            }
            //$this->validateFieldType($property, $value);

            if ($property->isScalar()) {
                $instance->{$item->name} = $value;
                continue;
            }

            if ($property->isArray()) {
                $arrayTypeAttr = $item->getAttributes(ConvertArray::class);
                if (!empty($arrayTypeAttr)) {
                    $arrayType = $arrayTypeAttr[0]->getArguments()[0];
                } else {
                    $arrayType = PropertyHelper::getClassFromPhpDoc($property->getDocComment());
                }

                if (!empty($arrayType) && !empty($value) && !PropertyHelper::propertyIsScalar($arrayType)) {
                    foreach ($value as $el) {
                        $instance->{$item->name}[] = self::init($arrayType, $el)->transform();
                    }
                    continue;
                }

                $instance->{$item->name} = $value;
                continue;
            }

            if ($property->getType() instanceof ReflectionNamedType) {
                $instance->{$item->name} = self::init($property->getType()->getName(), $value)->transform();
                continue;
            }
            $instance->{$item->name} = $value;
        }
        return $instance;
    }

    /**
     * Check the value for type compliance
     *
     * @param Property $item
     * @param mixed $value
     *
     * @return void
     */
    /*private function validateFieldType(Property $item, mixed $value): void
    {
        $clearType = match (gettype($value)) {
            'integer' => 'int',
            'boolean' => 'bool',
            'NULL' => 'null',
            default => gettype($value)
        };
        $types = $item->getTypes();
        if (in_array('float', $types)) {
            $types [] = 'double';
        }

        if (empty($types)) {
            return;
        }
        if (!in_array($clearType, $types)) {
            $propertyName = $item->property->getName();
            throw new InvalidArgumentException("The \"$propertyName\" property has the wrong type. Expected type ("
                . implode("|", $item->getTypes()) . "), received \"" . $clearType . "\"");
        }
    }*/

    /**
     * @param ReflectionProperty $item
     *
     * @return mixed|object|void
     * @throws ValueNotFoundException
     */
    private function searchValue(ReflectionProperty $item)
    {
        if (array_key_exists($item->name, $this->args)) {
            return $this->args[$item->name];
        }

        $writingStyle = $item->getAttributes(WritingStyle::class);

        if (empty($writingStyle)) {
            throw new ValueNotFoundException();
        }
        foreach ($writingStyle as $style) {
            $styles = $style->getArguments();
            if (
                (in_array(WritingStyle::STYLE_SNAKE_CASE, $styles) || in_array(WritingStyle::STYLE_ALL, $styles)) &&
                array_key_exists(WritingStyleUtil::strToSnakeCase($item->name), $this->args)
            ) {
                return $this->args[WritingStyleUtil::strToSnakeCase($item->name)];
            }
            if (
                (in_array(WritingStyle::STYLE_CAMEL_CASE, $styles) || in_array(WritingStyle::STYLE_ALL, $styles)) &&
                array_key_exists(WritingStyleUtil::strToCamelCase($item->name), $this->args)
            ) {
                return $this->args[WritingStyleUtil::strToCamelCase($item->name)];
            }
        }
        throw new ValueNotFoundException();
    }
}
