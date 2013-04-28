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
	 * @return array
	 */
	public function getAll()
	{
		return $this->aliases;
	}

}