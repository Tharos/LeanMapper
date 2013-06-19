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

use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Relationship\BelongsToMany;
use LeanMapper\Relationship\BelongsToOne;
use LeanMapper\Relationship\HasMany;
use LeanMapper\Relationship\HasOne;

/**
 * Entity property (field) reflection
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

	/** @var bool */
	private $containsCollection;

	/** @var HasOne|HasMany|BelongsToOne|BelongsToMany|null */
	private $relationship;

	/** @var PropertyFilters|null */
	private $propertyFilters;

	/** @var PropertyValuesEnum|null */
	private $propertyValuesEnum;

	/** @var string|null */
	private $extra;


	/**
	 * @param string $name
	 * @param string|null $column
	 * @param PropertyType $type
	 * @param bool $isWritable
	 * @param bool $isNullable
	 * @param bool $containsCollection
	 * @param HasOne|HasMany|BelongsToOne|BelongsToMany|null $relationship
	 * @param PropertyFilters|null $propertyFilters
	 * @param PropertyValuesEnum|null $propertyValuesEnum
	 * @param string|null $extra
	 */
	public function __construct($name, $column, PropertyType $type, $isWritable, $isNullable, $containsCollection, $relationship = null, PropertyFilters $propertyFilters = null, PropertyValuesEnum $propertyValuesEnum = null, $extra = null)
	{
		$this->name = $name;
		$this->column = $column;
		$this->type = $type;
		$this->isWritable = $isWritable;
		$this->isNullable = $isNullable;
		$this->containsCollection = $containsCollection;
		$this->relationship = $relationship;
		$this->propertyFilters = $propertyFilters;
		$this->propertyValuesEnum = $propertyValuesEnum;
		$this->extra = $extra;
	}

	/**
	 * Returns property name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns property column
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
	 * Returns property type
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
	 * Returns property filters
	 *
	 * @param int|null $index
	 * @return string[]|null
	 */
	public function getFilters($index = null)
	{
		return $this->propertyFilters !== null ? $this->propertyFilters->getFilters($index) : null;
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
	 * Tells wheter given value is from enumeration of possible values
	 *
	 * @param mixed $value
	 * @return bool
	 * @throws InvalidMethodCallException
	 */
	public function isValueFromEnum($value)
	{
		if (!$this->containsEnumeration()) {
			throw new InvalidMethodCallException("It doesn't make sense to call this method on property that doesn't contain enumeration");
		}
		return $this->propertyValuesEnum->isValueFromEnum($value);
	}

	/**
	 * Returns value of m:extra flag (if given)
	 *
	 * @return string|null
	 */
	public function getExtra()
	{
		return $this->extra;
	}

}
