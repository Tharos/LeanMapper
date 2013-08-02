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

	const FILTER_TYPE_SIMPLE = 'simple';

	const FILTER_TYPE_PROPERTY = 'property';

	/** @var array */
	private $filters;


	/**
	 * @param string $name
	 * @param mixed $callback
	 * @param string $argsMode
	 * @throws InvalidArgumentException
	 */
	public function registerFilter($name, $callback, $argsMode = self::FILTER_TYPE_SIMPLE)
	{
		if (isset($this->filters[$name])) {
			throw new InvalidArgumentException("Filter with name '$name' was already registered.");
		}
		if (!is_callable($callback, true)) {
			throw new InvalidArgumentException("Callback given for filter '$name' is not callable.");
		}
		if ($argsMode !== self::FILTER_TYPE_SIMPLE and $argsMode !== self::FILTER_TYPE_PROPERTY) {
			throw new InvalidArgumentException("Invalid filter arguments mode given for filter with name '$name': $argsMode");
		}
		$this->filters[$name] = array($callback, $argsMode);
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
	 * @param string $name
	 * @return string
	 */
	public function getFilterArgsMode($name)
	{
		$this->checkFilterExistence($name);
		return $this->filters[$name][1];
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
