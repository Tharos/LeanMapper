<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Reflection\Property;

/**
 * Encapsulation of filter call
 *
 * @author Vojtěch Kohout
 */
class Filtering
{

	/** @var array */
	private $filters;

	/** @var array */
	private $args;

	/** @var Entity|null */
	private $entity;

	/** @var Property|null */
	private $property;

	/** @var array */
	private $namedArgs;


	/**
	 * @param string|array $filters
	 * @param array|null $args
	 * @param Entity|null $entity
	 * @param Property|null $property
	 * @param array|null $namedArgs
	 * @throws InvalidArgumentException
	 */
	public function __construct($filters, array $args = null, Entity $entity = null, Property $property = null, array $namedArgs = array())
	{
		if (!is_array($filters)) {
			if (!is_string($filters)) {
				throw new InvalidArgumentException("Argument \$filters must contain either string (name of filter) or array (names of filters).");
			}
			$filters = array($filters);
		}
		$this->filters = $filters;
		$this->args = $args !== null ? $args : array();
		$this->entity = $entity;
		$this->property = $property;
		$this->namedArgs = $namedArgs;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * @return array
	 */
	public function getArgs()
	{
		return $this->args;
	}

	/**
	 * @return Entity|null
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * @return Property|null
	 */
	public function getProperty()
	{
		return $this->property;
	}

	/**
	 * @return array
	 */
	public function getNamedArgs()
	{
		return $this->namedArgs;
	}

}
