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


	public function resetCurrent()
	{
		$this->current = $this->lastPart = '';
	}

	/**
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
	 * @param string $name
	 */
	public function setLast($name)
	{
		$this->lastPart = $name;
	}

	public function finishCurrent()
	{
		$this->aliases[$this->lastPart] = $this->current;
		$this->resetCurrent();
	}

	/**
	 * @param string $namespace
	 * @return Aliases
	 */
	public function getAliases($namespace = '')
	{
		return new Aliases($this->aliases, $namespace);
	}

}