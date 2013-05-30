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

use LeanMapper\Relationship\BelongsToMany;
use LeanMapper\Relationship\BelongsToOne;
use LeanMapper\Relationship\HasMany;
use LeanMapper\Relationship\HasOne;

/**
 * @author Vojtěch Kohout
 */
class Property
{

	/** @var string */
	private $name;

	/** @var PropertyType */
	private $type;

	/** @var bool */
	private $isNullable;

	/** @var bool */
	private $containsCollection;

	/** @var HasOne|HasMany|BelongsToOne|BelongsToMany|null */
	private $relationship;

	/** @var PropertyFilters|null */
	private $filters;


	/**
	 * @param string $name
	 * @param PropertyType $type
	 * @param bool $isNullable
	 * @param bool $containsCollection
	 * @param HasOne|HasMany|BelongsToOne|BelongsToMany|null $relationship
	 * @param PropertyFilters|null $filters
	 */
	public function __construct($name, PropertyType $type, $isNullable, $containsCollection, $relationship = null, PropertyFilters $filters = null)
	{
		$this->name = $name;
		$this->type = $type;
		$this->isNullable = $isNullable;
		$this->containsCollection = $containsCollection;
		$this->relationship = $relationship;
		$this->filters = $filters;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function containsCollection()
	{
		return $this->containsCollection;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type->getType();
	}

	/**
	 * @return bool
	 */
	public function isBasicType()
	{
		return $this->type->isBasicType();
	}

	/**
	 * @return bool
	 */
	public function isNullable()
	{
		return $this->isNullable;
	}

	/**
	 * @return bool
	 */
	public function hasRelationship()
	{
		return $this->relationship !== null;
	}

	/**
	 * @return BelongsToMany|BelongsToOne|HasMany|HasOne|null
	 */
	public function getRelationship()
	{
		return $this->relationship;
	}

	/**
	 * @return string[]
	 */
	public function getFilters()
	{
		return $this->filters !== null ? $this->filters->getFilters() : null;
	}

}
