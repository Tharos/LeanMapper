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

use DibiFluent;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Reflection\Property;

/**
 * DibiFluent with filter support
 *
 * @author Vojtěch Kohout
 */
class Fluent extends DibiFluent
{

	/**
	 * @param string $name
	 * @param mixed|null $args
	 * @return $this
	 */
	public function applyFilter($name, $args = null)
	{
		$args = func_get_args();
		$args[0] = $this;
		call_user_func_array($this->getConnection()->getFilterCallback($name), $args);
		return $this;
	}

	/**
	 * @param string $name
	 * @param Entity $entity
	 * @param Property $property
	 * @param mixed|null $args
	 * @return $this
	 * @throws InvalidMethodCallException
	 */
	public function applyFilterFromProperty($name, Entity $entity, Property $property, $args = null)
	{
		$connection = $this->getConnection();
		if ($connection->getFilterArgsMode($name) !== Connection::FILTER_ARGS_RICH) {
			throw new InvalidMethodCallException("Filter '$name' cannot be called using Fluent::applyFilterFromProperty.");
		}
		$args = func_get_args();
		$args[0] = $this;
		call_user_func_array($this->connection->getFilterCallback($name), $args);
		return $this;
	}

}
