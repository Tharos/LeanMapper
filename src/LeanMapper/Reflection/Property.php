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

use LeanMapper;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Relationship;

/**
 * Reflection of entity's property
 *
 * @author Vojtěch Kohout
 */
class Property
{

    /** @var string */
    private $name;

    /** @var string|null */
    private $column;

    /** @var EntityReflection */
    private $entityReflection;

    /** @var PropertyType */
    private $type;

    /** @var bool */
    private $isWritable;

    /** @var bool */
    private $isNullable;

    /** @var bool */
    private $hasDefaultValue;

    /** @var mixed|null */
    private $defaultValue;

    /** @var bool */
    private $containsCollection;

    /** @var Relationship\HasOne|Relationship\HasMany|Relationship\BelongsToOne|Relationship\BelongsToMany|null */
    private $relationship;

    /** @var PropertyMethods|null */
    private $propertyMethods;

    /** @var PropertyFilters|null */
    private $propertyFilters;

    /** @var PropertyPasses|null */
    private $propertyPasses;

    /** @var PropertyValuesEnum|null */
    private $propertyValuesEnum;

    /** @var array<string, mixed> */
    private $customFlags;


    /**
     * @param mixed|null $defaultValue
     * @param Relationship\HasOne|Relationship\HasMany|Relationship\BelongsToOne|Relationship\BelongsToMany|null $relationship
     * @param array<string, mixed> $customFlags
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $name,
        EntityReflection $entityReflection,
        ?string $column,
        PropertyType $type,
        bool $isWritable,
        bool $isNullable,
        bool $containsCollection,
        bool $hasDefaultValue,
        $defaultValue = null,
        $relationship = null,
        ?PropertyMethods $propertyMethods = null,
        ?PropertyFilters $propertyFilters = null,
        ?PropertyPasses $propertyPasses = null,
        ?PropertyValuesEnum $propertyValuesEnum = null,
        array $customFlags = []
    ) {
        if ($relationship !== null) {
            if (!is_subclass_of($type->getType(), LeanMapper\Entity::class)) {
                throw new InvalidArgumentException(
                    "Property '$name' in entity {$entityReflection->getName()} cannot contain relationship since it doesn't contain entity (or collection of entities)."
                );
            }
            if (($relationship instanceof Relationship\HasMany) or ($relationship instanceof Relationship\BelongsToMany)) {
                if (!$containsCollection) {
                    throw new InvalidArgumentException(
                        "Property '$name' with HasMany or BelongsToMany in entity {$entityReflection->getName()} relationship must contain collection."
                    );
                }
            } else {
                if ($containsCollection) {
                    throw new InvalidArgumentException(
                        "Property '$name' with HasOne or BelongsToOne in entity {$entityReflection->getName()} relationship cannot contain collection."
                    );
                }
            }
            if ($propertyPasses !== null) {
                throw new InvalidArgumentException(
                    "Property '$name' in entity {$entityReflection->getName()} cannot have defined m:passThru since it contains relationship."
                );
            }
        } elseif ($propertyFilters !== null) {
            throw new InvalidArgumentException(
                "Cannot bind filter to property '$name' in entity {$entityReflection->getName()} since it doesn't contain relationship."
            );
        }
        if ($propertyValuesEnum !== null and (!$type->isBasicType() or $type->getType() === 'array' or $containsCollection)) {
            throw new InvalidArgumentException("Values of property '$name' in entity {$entityReflection->getName()} cannot be enumerated.");
        }
        $this->name = $name;
        $this->entityReflection = $entityReflection;
        $this->column = $column;
        $this->type = $type;
        $this->isWritable = $isWritable;
        $this->isNullable = $isNullable;
        $this->containsCollection = $containsCollection;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->relationship = $relationship;
        $this->propertyMethods = $propertyMethods;
        $this->propertyFilters = $propertyFilters;
        $this->propertyPasses = $propertyPasses;
        $this->propertyValuesEnum = $propertyValuesEnum;
        $this->customFlags = $customFlags;

    }


    /**
     * Gets property name
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Gets name of column holding low-level value
     */
    public function getColumn(): ?string
    {
        return $this->column;
    }


    /**
     * Tells whether property is assumed to contain collection
     */
    public function containsCollection(): bool
    {
        return $this->containsCollection;
    }


    /**
     * Tells whether property has default value (defined in annotation)
     */
    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }


    /**
     * Gets default value of property (as defined in annotation)
     *
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }


    /**
     * Gets property type
     */
    public function getType(): string
    {
        return $this->type->getType();
    }


    /**
     * Tells whether property type is basic type (boolean|integer|float|string|array)
     */
    public function isBasicType(): bool
    {
        return $this->type->isBasicType();
    }


    /**
     * Tells whether property is writable
     */
    public function isWritable(): bool
    {
        return $this->isWritable;
    }


    /**
     * Tells whether property can be null
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }


    /**
     * Tells whether property represents relationship
     */
    public function hasRelationship(): bool
    {
        return $this->relationship !== null;
    }


    /**
     * Returns relationship that property represents
     *
     * @return Relationship\BelongsToMany|Relationship\BelongsToOne|Relationship\HasMany|Relationship\HasOne|null
     */
    public function getRelationship()
    {
        return $this->relationship;
    }


    /**
     * Gets getter method
     */
    public function getGetter(): ?string
    {
        return $this->propertyMethods !== null ? $this->propertyMethods->getGetter() : null;
    }


    /**
     * Gets setter method
     */
    public function getSetter(): ?string
    {
        return $this->propertyMethods !== null ? $this->propertyMethods->getSetter() : null;
    }


    /**
     * Gets property filters
     *
     * @return array<string>|null
     */
    public function getFilters(int $index = 0): ?array
    {
        return $this->propertyFilters !== null ? $this->propertyFilters->getFilters($index) : null;
    }


    /**
     * Gets filters arguments hard-coded in annotation
     *
     * @return array<string, array<mixed>>|null
     */
    public function getFiltersTargetedArgs(int $index = 0): ?array
    {
        return $this->propertyFilters !== null ? $this->propertyFilters->getFiltersTargetedArgs($index) : null;
    }


    /**
     * Gets getter pass
     */
    public function getGetterPass(): ?string
    {
        return $this->propertyPasses !== null ? $this->propertyPasses->getGetterPass() : null;
    }


    /**
     * Gets setter pass
     */
    public function getSetterPass(): ?string
    {
        return $this->propertyPasses !== null ? $this->propertyPasses->getSetterPass() : null;
    }


    /**
     * Tells whether property contains enumeration
     */
    public function containsEnumeration(): bool
    {
        return $this->propertyValuesEnum !== null;
    }


    /**
     * Tells wheter given value is from enumeration
     *
     * @param mixed $value
     * @throws InvalidMethodCallException
     */
    public function isValueFromEnum($value): bool
    {
        $this->checkContainsEnumeration();
        return $this->propertyValuesEnum->isValueFromEnum($value);
    }


    /**
     * Gets possible enumeration values
     *
     * @return array<string, mixed>
     */
    public function getEnumValues(): array
    {
        $this->checkContainsEnumeration();
        return $this->propertyValuesEnum->getValues();
    }


    /**
     * Tells whether property has custom flag
     */
    public function hasCustomFlag(string $name): bool
    {
        return array_key_exists($name, $this->customFlags);
    }


    /**
     * Gets value of requested custom flag
     *
     * @throws InvalidArgumentException
     */
    public function getCustomFlagValue(string $name): string
    {
        if (!$this->hasCustomFlag($name)) {
            throw new InvalidArgumentException(
                "Property '{$this->name}' in entity {$this->entityReflection->getName()} doesn't have custom flag '$name'."
            );
        }
        return $this->customFlags[$name];
    }

    //////////

    /**
     * @throws InvalidMethodCallException
     */
    private function checkContainsEnumeration(): void
    {
        if (!$this->containsEnumeration()) {
            throw new InvalidMethodCallException(
                "It doesn't make sense to call enumeration related method on property '{$this->name}' in entity {$this->entityReflection->getName()} since it doesn't contain enumeration."
            );
        }
    }

}
