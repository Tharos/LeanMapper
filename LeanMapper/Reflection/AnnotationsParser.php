<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\UtilityClassException;

/**
 * @author Vojtěch Kohout
 */
class AnnotationsParser
{

	/**
	 * @throws UtilityClassException
	 */
	public function __construct()
	{
		throw new UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
	}

	/**
	 * @param string $annotation
	 * @param string $docComment
	 * @return string|null
	 */
	public static function parseSimpleAnnotationValue($annotation, $docComment)
	{
		$matches = array();
		preg_match("#@$annotation\\s+([^\\s]+)#", $docComment, $matches);
		return !empty($matches) ? $matches[1] : null;
	}

	/**
	 * @param string $annotation
	 * @param string $docComment
	 * @return array
	 */
	public static function parseAnnotationValues($annotation, $docComment)
	{
		$matches = array();
		preg_match_all("#@$annotation\\s+([^@\\n\\r]*)#", $docComment, $matches);
		return $matches[1];
	}
	
}
