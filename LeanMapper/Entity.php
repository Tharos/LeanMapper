<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper;

use LeanMapper\Reflection\EntityReflection;

/**
 * @author Vojtěch Kohout
 */
abstract class Entity
{

	/** @var EntityReflection[] */
	protected static $reflections = array();


	/**
	 * @return EntityReflection
	 */
	public static function getReflection()
	{
		$class = get_called_class();
		if (!isset(static::$reflections[$class])) {
			static::$reflections[$class] = new EntityReflection($class);
		}

		return static::$reflections[$class];
	}
	
}
