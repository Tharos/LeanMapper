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
	public function applyPropertyFilter($name, Entity $entity, Property $property, $args = null)
	{
		$args = func_get_args();
		$args[0] = $this;
		$connection = $this->getConnection();
		if ($connection->getFilterArgsMode($name) === Connection::FILTER_TYPE_PROPERTY) {
			call_user_func_array($this->connection->getFilterCallback($name), $args);
		} else {
			$args = array_slice($args, 3);
			if (empty($args)) {
				$args = array($name);
			} else {
				array_unshift($args, $name);
			}
			call_user_func_array(array($this, 'applyFilter'), $args);
		}
		return $this;
	}

}
