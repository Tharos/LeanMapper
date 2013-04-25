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

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Exception\UtilityClassException;

/**
 * @author Vojtěch Kohout
 */
class PropertyParser
{

	/**
	 * @throws UtilityClassException
	 */
	public function __construct()
	{
		throw new UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
	}

	/**
	 * @param string $type
	 * @param string $annotation
	 * @param string $namespace
	 * @param array $aliases
	 * @return Property
	 * @throws InvalidAnnotationException
	 */
	public static function parseFromAnnotation($type, $annotation, $namespace, array $aliases)
	{
		$matches = array();
		$matched = preg_match('~
			^(null\|)?
			((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)
			(\[\])?
			(\|null)? \s+
			(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)
			(?:\s+m:(?:(hasOne|hasMany|belongsToOne|belongsToMany)(\\([^)]+\\))?))?
		~xi', $annotation, $matches);

		if (!$matched) {
			throw new InvalidAnnotationException("Invalid property annotation given: @$type $annotation");
		}

		return new Property(
			substr($matches[5], 1),
			$type === 'property',
			$matches[3] !== '',
			$matches[2],
			$matches[1] !== '' or $matches[3] !== '',
			$namespace,
			$aliases
		);
		// TODO: relationships!
	}

}
