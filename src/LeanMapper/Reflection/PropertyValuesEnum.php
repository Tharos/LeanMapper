<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

declare(strict_types=1);

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Exception\InvalidStateException;
use ReflectionClass;

/**
 * Enumeration of possible property values
 *
 * @author Vojtěch Kohout
 */
class PropertyValuesEnum
{

    /** @var array<string, mixed> */
    private $values = [];

    /** @var array<mixed, true> */
    private $index = [];


    /**
     * @param array<string, mixed> $values
     * @param array<mixed, true> $index
     */
    public function __construct(array $values, array $index)
    {
        $this->values = $values;
        $this->index = $index;
    }


    /**
     * Tells wheter given value is from enumeration
     *
     * @param mixed $value
     */
    public function isValueFromEnum($value): bool
    {
        return isset($this->index[$value]);
    }


    /**
     * Gets possible enumeration values
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }


    /**
     * @throws InvalidAnnotationException
     */
    public static function createFromDefinition(string $definition, EntityReflection $reflection): self
    {
        $matches = [];
        preg_match(
            '#^((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+|self|static|parent)::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)?\*$#',
            $definition,
            $matches
        );
        if (empty($matches)) {
            throw new InvalidAnnotationException("Invalid enumeration definition given: '$definition'.");
        }
        $class = $matches[1];
        $prefix = array_key_exists(2, $matches) ? $matches[2] : '';

        if ($class === 'self' or $class === 'static') {
            $constants = $reflection->getConstants();
        } elseif ($class === 'parent') {
            $constants = $reflection->getParentClass()->getConstants();
        } else {
            if (substr($class, 0, 1) === '\\') {
                $className = substr($class, 1);

            } else {
                $aliases = $reflection->getAliases();
                $className = $aliases->translate($class);
            }

            if (!class_exists($className) && !interface_exists($className)) {
                throw new InvalidStateException("Class or interface $className not found.");
            }

            $reflectionClass = new ReflectionClass($className);
            $constants = $reflectionClass->getConstants();
        }
        $values = [];
        $index = [];
        foreach ($constants as $name => $value) {
            if (substr($name, 0, strlen($prefix)) === $prefix) {
                $values[$name] = $value;
                $index[$value] = true;
            }
        }

        return new static($values, $index);
    }

}
