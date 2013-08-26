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
use ReflectionClass;

/**
 * Enumeration of possible property values
 *
 * @author Vojtěch Kohout
 */
class PropertyValuesEnum
{

	/** @var array */
	private $values = array();

	/** @var array */
	private $index = array();


	/**
	 * @param string $definition
	 * @param EntityReflection $reflection
	 * @throws InvalidAnnotationException
	 */
	public function __construct($definition, EntityReflection $reflection)
	{
		$matches = array();
		preg_match('#^((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+|self|static|parent)::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)\*$#', $definition, $matches);
		if (empty($matches)) {
			throw new InvalidAnnotationException("Invalid enumeration definition given: '$definition'.");
		}
		list(, $class, $prefix) = $matches;

		if ($class === 'self' or $class === 'static') {
			$constants = $reflection->getConstants();
		} elseif ($class === 'parent') {
			$constants = $reflection->getParentClass()->getConstants();
		} else {
			$aliases = $reflection->getAliases();
			$reflectionClass = new ReflectionClass($aliases->translate($class));
			$constants = $reflectionClass->getConstants();
		}
		foreach ($constants as $name => $value) {
			if (substr($name, 0, strlen($prefix)) === $prefix) {
				$this->values[$name] = $value;
				$this->index[$value] = true;
			}
		}
	}

	/**
	 * Tells wheter given value is from enumeration
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function isValueFromEnum($value)
	{
		return isset($this->index[$value]);
	}

	/**
	 * Gets possible enumeration values
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

}
