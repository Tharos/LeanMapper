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

use LeanMapper\Exception\UtilityClassException;

/**
 * Simple class annotations parser
 *
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
	 * Parse value of requested simple annotation from given doc comment
	 *
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
	 * Parse value pieces of requested annotation from given doc comment
	 *
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
