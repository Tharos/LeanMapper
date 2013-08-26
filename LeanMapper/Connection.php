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

use DibiConnection;
use LeanMapper\Exception\InvalidArgumentException;

/**
 * DibiConnection with filter support
 *
 * @author Vojtěch Kohout
 */
class Connection extends DibiConnection
{

	const WIRE_ENTITY = 1;

	const WIRE_PROPERTY = 2;

	const WIRE_ENTITY_AND_PROPERTY = 3;

	/** @var array */
	private $filters;


	/**
	 * Registers new filter
	 *
	 * @param string $name
	 * @param mixed $callback
	 * @param string|int|null $wiringSchema
	 * @throws InvalidArgumentException
	 */
	public function registerFilter($name, $callback, $wiringSchema = null)
	{
		if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $name)) {
			throw new InvalidArgumentException("Invalid filter name given: '$name'. For filter names apply the same rules as for function names in PHP.");
		}
		if (isset($this->filters[$name])) {
			throw new InvalidArgumentException("Filter with name '$name' was already registered.");
		}
		if (!is_callable($callback, true)) {
			throw new InvalidArgumentException("Callback given for filter '$name' is not callable.");
		}
		$this->filters[$name] = array($callback, $this->translateWiringSchema($wiringSchema));
	}

	/**
	 * Gets callable filter's callback
	 *
	 * @param string $name
	 * @return callable
	 */
	public function getFilterCallback($name)
	{
		$this->checkFilterExistence($name);
		return $this->filters[$name][0];
	}

	/**
	 * Gets wiring schema
	 *
	 * @param string $filterName
	 * @return string
	 */
	public function getWiringSchema($filterName)
	{
		$this->checkFilterExistence($filterName);
		return $this->filters[$filterName][1];
	}

	/**
	 * Creates new instance of Fluent
	 *
	 * @return Fluent
	 */
	public function command()
	{
		return new Fluent($this);
	}

	////////////////////
	////////////////////

	/**
	 * @param string $name
	 * @throws InvalidArgumentException
	 */
	private function checkFilterExistence($name)
	{
		if (!isset($this->filters[$name])) {
			throw new InvalidArgumentException("Filter with name '$name' was not found.");
		}
	}

	/**
	 * @param string|int|null $wiringSchema
	 * @return string
	 * @throws InvalidArgumentException
	 */
	private function translateWiringSchema($wiringSchema)
	{
		if ($wiringSchema === null) {
			return '';
		}
		if (is_int($wiringSchema)) {
			$result = '';
			if ($wiringSchema & self::WIRE_ENTITY) {
				$result .= 'e';
			}
			if ($wiringSchema & self::WIRE_PROPERTY) {
				$result .= 'p';
			}
			$wiringSchema = $result;
		} elseif (!preg_match('#^(?:([pe])(?!.*\1))*$#', $wiringSchema)) {
			throw new InvalidArgumentException("Invalid wiring schema given: '$wiringSchema'. Please use only characters p (Property) and e (Entity) in unique, non-repeating combination.");
		}
		return $wiringSchema;
	}

}
