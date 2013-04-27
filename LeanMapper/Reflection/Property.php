<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
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
	private $isWritable;

	/** @var bool */
	private $isNullable;

	/** @var bool */
	private $containsCollection;

	/** @var HasOne|HasMany|BelongsToOne|BelongsToMany|null */
	private $relationship;


	/**
	 * @param string $name
	 * @param PropertyType $type
	 * @param bool $isNullable
	 * @param bool $containsCollection
	 * @param HasOne|HasMany|BelongsToOne|BelongsToMany|null $relationship
	 */
	public function __construct($name, PropertyType $type, $isNullable, $containsCollection, $relationship = null)
	{
		$this->name = $name;
		$this->type = $type;
		$this->isNullable = $isNullable;
		$this->containsCollection = $containsCollection;
		$this->relationship = $relationship;
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

}
