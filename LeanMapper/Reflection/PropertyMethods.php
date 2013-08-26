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

use LeanMapper\Exception\InvalidAnnotationException;

/**
 * Set of property access methods (given in useMethods flag)
 *
 * @author Vojtěch Kohout
 */
class PropertyMethods
{

	/** @var string */
	private $getter;

	/** @var string */
	private $setter;


	/**
	 * @param string $propertyName
	 * @param bool $isWritable
	 * @param string $definition
	 * @throws InvalidAnnotationException
	 */
	public function __construct($propertyName, $isWritable, $definition)
	{
		$ucName = ucfirst($propertyName);
		$this->getter = 'get' . $ucName;
		if ($isWritable) {
			$this->setter = 'set' . $ucName;
		}
		$counter = 0;
		foreach (preg_split('#\s*\|\s*#', trim($definition)) as $method) {
			$counter++;
			if ($counter > 2) {
				throw new InvalidAnnotationException('Property methods cannot have more than two parts.');
			}
			if ($method === '') {
				continue;
			}
			if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $method)) {
				throw new InvalidAnnotationException("Malformed access method name given: '$method'.");
			}
			if ($counter === 1) {
				$this->getter = $method;
			} else { // $counter === 2
				if (!$isWritable) {
					throw new InvalidAnnotationException('Property methods can have one part only in read-only properties.');
				}
				$this->setter = $method;
			}
		}
	}

	/**
	 * Gets getter method
	 *
	 * @return string|null
	 */
	public function getGetter()
	{
		return $this->getter;
	}

	/**
	 * Gets setter method
	 *
	 * @return string|null
	 */
	public function getSetter()
	{
		return $this->setter;
	}

}
