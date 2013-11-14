<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

/**
 * Encapsulation of entity filters
 *
 * @author Vojtěch Kohout
 */
class EntityFilters
{

	/** @var array */
	private $filters;

	/** @var array */
	private $namedArgs;


	/**
	 * @param array $filters
	 * @param array|null $namedArgs
	 */
	public function __construct(array $filters, array $namedArgs = array())
	{
		$this->filters = $filters;
		$this->namedArgs = $namedArgs;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * @return array
	 */
	public function getNamedArgs()
	{
		return $this->namedArgs;
	}

}
