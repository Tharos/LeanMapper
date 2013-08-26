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

/**
 * Class names translator
 *
 * @author Vojtěch Kohout
 */
class Aliases
{

	/** @var array */
	private $aliases;

	/** @var string */
	private $namespace;


	/**
	 * @param array $aliases
	 * @param string $namespace
	 */
	public function __construct(array $aliases, $namespace = '')
	{
		$this->aliases = $aliases;
		$this->namespace = $namespace;
	}

	/**
	 * Determines fully qualified class name
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function translate($identifier)
	{
		$pieces = explode('\\', $identifier);
		if (isset($this->aliases[$pieces[0]])) {
			$return = $this->aliases[$pieces[0]];
			if (count($pieces) > 1) {
				array_shift($pieces);
				$return .= '\\' . implode('\\', $pieces);
			}
		} else {
			$return = $this->namespace !== '' ? $this->namespace . '\\' . $identifier : $identifier;
		}
		return $return;
	}

}