<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper\Reflection;

/**
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