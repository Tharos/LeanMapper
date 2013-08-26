<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Relationship\BelongsToMany;
use LeanMapper\Relationship\BelongsToOne;
use LeanMapper\Relationship\HasMany;
use LeanMapper\Relationship\HasOne;

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

	/** @var PropertyType */
	private $type;

	/** @var bool */
	private $isWritable;

	/** @var bool */
	private $isNullable;

	/** @var mixed|null */
	private $defaultValue;

	/** @var bool */
	private $containsCollection;

	/** @var HasOne|HasMany|BelongsToOne|BelongsToMany|null */
	private $relationship;

	/** @var PropertyMethods|null */
	private $propertyMethods;

	/** @var PropertyFilters|null */
	private $propertyFilters;

	/** @var PropertyPasses|null */
	private $propertyPasses;

	/** @var PropertyValuesEnum|null */
	private $propertyValuesEnum;

	/** @var array */
	private $customFlags;


	/**
	 * @param string $name
	 * @param string|null $column
	 * @param PropertyType $type
	 * @param bool $isWritable
	 * @param bool $isNullable
	 * @param bool $containsCollection
	 * @param mixed|null $defaultValue
	 * @param HasOne|HasMany|BelongsToOne|BelongsToMany|null $relationship
	 * @param PropertyMethods|null $propertyMethods
	 * @param PropertyFilters|null $propertyFilters
	 * @param PropertyPasses|null $propertyPasses
	 * @param PropertyValuesEnum|null $propertyValuesEnum
	 * @param array|null $customFlags
	 * @throws InvalidArgumentException
	 */
	public function __construct($name, $column, PropertyType $type, $isWritable, $isNullable, $containsCollection, $defaultValue = null, $relationship = null, PropertyMethods $propertyMethods = null, PropertyFilters $propertyFilters = null, PropertyPasses $propertyPasses = null, PropertyValuesEnum $propertyValuesEnum = null, array $customFlags = array())
	{
		if ($propertyFilters !== null and $relationship === null) {
			throw new InvalidArgumentException('Cannot bind filter to property without relationship.');
		}
		$this->name = $name;
		$this->column = $column;
		$this->type = $type;
		$this->isWritable = $isWritable;
		$this->isNullable = $isNullable;
		$this->containsCollection = $containsCollection;
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
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Gets name of column holding low-level value
	 *
	 * @return string|null
	 */
	public function getColumn()
	{
		return $this->column;
	}

	/**
	 * Tells whether property is assumed to contain collection
	 *
	 * @return bool
	 */
	public function containsCollection()
	{
		return $this->containsCollection;
	}

	/**
	 * Tells whether property has default value (defined in annotation)
	 *
	 * @return bool
	 */
	public function hasDefaultValue()
	{
		return $this->defaultValue !== null;
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
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type->getType();
	}

	/**
	 * Tells whether property type is basic type (boolean|integer|float|string|array)
	 *
	 * @return bool
	 */
	public function isBasicType()
	{
		return $this->type->isBasicType();
	}

	/**
	 * Tells whether property is writable
	 *
	 * @return bool
	 */
	public function isWritable()
	{
		return $this->isWritable;
	}

	/**
	 * Tells whether property can be null
	 *
	 * @return bool
	 */
	public function isNullable()
	{
		return $this->isNullable;
	}

	/**
	 * Tells whether property represents relationship
	 *
	 * @return bool
	 */
	public function hasRelationship()
	{
		return $this->relationship !== null;
	}

	/**
	 * Returns relationship that property represents
	 *
	 * @return BelongsToMany|BelongsToOne|HasMany|HasOne|null
	 */
	public function getRelationship()
	{
		return $this->relationship;
	}

	/**
	 * Gets getter method
	 *
	 * @return string|null
	 */
	public function getGetter()
	{
		return $this->propertyMethods !== null ? $this->propertyMethods->getGetter() : null;
	}

	/**
	 * Gets setter method
	 *
	 * @return string|null
	 */
	public function getSetter()
	{
		return $this->propertyMethods !== null ? $this->propertyMethods->getSetter() : null;
	}

	/**
	 * Gets property filters
	 *
	 * @param int $index
	 * @return array|null
	 */
	public function getFilters($index = 0)
	{
		return $this->propertyFilters !== null ? $this->propertyFilters->getFilters($index) : null;
	}

	/**
	 * Gets filters arguments hard-coded in annotation
	 *
	 * @param int $index
	 * @return array|string|null
	 */
	public function getFiltersAnnotationArgs($index = 0)
	{
		return $this->propertyFilters !== null ? $this->propertyFilters->getFiltersAnnotationArgs($index) : null;
	}

	/**
	 * Gets getter pass
	 *
	 * @return string|null
	 */
	public function getGetterPass()
	{
		return $this->propertyPasses !== null ? $this->propertyPasses->getGetterPass() : null;
	}

	/**
	 * Gets setter pass
	 *
	 * @return string|null
	 */
	public function getSetterPass()
	{
		return $this->propertyPasses !== null ? $this->propertyPasses->getSetterPass() : null;
	}

	/**
	 * Tells whether property contains enumeration
	 *
	 * @return bool
	 */
	public function containsEnumeration()
	{
		return $this->propertyValuesEnum !== null;
	}

	/**
	 * Tells wheter given value is from enumeration
	 *
	 * @param mixed $value
	 * @return bool
	 * @throws InvalidMethodCallException
	 */
	public function isValueFromEnum($value)
	{
		$this->checkContainsEnumeration();
		return $this->propertyValuesEnum->isValueFromEnum($value);
	}

	/**
	 * Gets possible enumeration values
	 *
	 * @return array
	 */
	public function getEnumValues()
	{
		$this->checkContainsEnumeration();
		return $this->propertyValuesEnum->getValues();
	}

	/**
	 * Tells whether property has custom flag
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasCustomFlag($name)
	{
		return array_key_exists($name, $this->customFlags);
	}

	/**
	 * Gets value of requested custom flag
	 *
	 * @param string $name
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getCustomFlagValue($name)
	{
		if (!$this->hasCustomFlag($name)) {
			throw new InvalidArgumentException("Property doesn't have custom flag '$name'.");
		}
		return $this->customFlags[$name];
	}

	//////////

	/**
	 * @throws InvalidMethodCallException
	 */
	private function checkContainsEnumeration()
	{
		if (!$this->containsEnumeration()) {
			throw new InvalidMethodCallException("It doesn't make sense to call this method on property that doesn't contain enumeration.");
		}
	}

}
