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
 * @author Vojtěch Kohout
 */
class AliasesBuilder
{

	/** @var array */
	private $aliases = array();

	/** @var string */
	private $current = '';

	/** @var string */
	private $lastPart = '';


	/**
	 * Sets current definition to empty string
	 */
	public function resetCurrent()
	{
		$this->current = $this->lastPart = '';
	}

	/**
	 * Appends name to current definition
	 *
	 * @param string $name
	 */
	public function appendToCurrent($name)
	{
		if ($this->current !== '') {
			$this->current .= '\\';
		}
		$this->current .= $this->lastPart = $name;
	}

	/**
	 * Appends last part to current definition
	 *
	 * @param string $name
	 */
	public function setLast($name)
	{
		$this->lastPart = $name;
	}

	/**
	 * Finishes building of current definition and begins to build new one
	 */
	public function finishCurrent()
	{
		$this->aliases[$this->lastPart] = $this->current;
		$this->resetCurrent();
	}

	/**
	 * Creates new Aliases instance
	 *
	 * @param string $namespace
	 * @return Aliases
	 */
	public function getAliases($namespace = '')
	{
		return new Aliases($this->aliases, $namespace);
	}

}