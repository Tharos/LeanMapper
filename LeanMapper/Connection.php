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

	/** @var array */
	private $filters;


	/**
	 * @param string $name
	 * @param mixed $callback
	 * @param string|null $autowiringSchema
	 * @throws InvalidArgumentException
	 */
	public function registerFilter($name, $callback, $autowiringSchema = null)
	{
		if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $name)) {
			throw new InvalidArgumentException("Invalid filter name given: $name. For filter names apply the same rules as for function names in PHP.");
		}
		if (isset($this->filters[$name])) {
			throw new InvalidArgumentException("Filter with name '$name' was already registered.");
		}
		if (!is_callable($callback, true)) {
			throw new InvalidArgumentException("Callback given for filter '$name' is not callable.");
		}
		if ($autowiringSchema === null) {
			$autowiringSchema = '';
		} elseif (!preg_match('#^(?:([pe])(?!.*\1))*$#', $autowiringSchema)) {
			throw new InvalidArgumentException("Unsupported autowiring schema given: $autowiringSchema. Please use only characters p (Property) and e (Entity) in unique, non-repeating combination.");
		}
		$this->filters[$name] = array($callback, $autowiringSchema);
	}

	/**
	 * @param string $name
	 * @return callable
	 */
	public function getFilterCallback($name)
	{
		$this->checkFilterExistence($name);
		return $this->filters[$name][0];
	}

	/**
	 * @param string $filterName
	 * @return string
	 */
	public function getAutowiringSchema($filterName)
	{
		$this->checkFilterExistence($filterName);
		return $this->filters[$filterName][1];
	}

	/**
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

}
