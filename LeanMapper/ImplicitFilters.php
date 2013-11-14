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
 * Encapsulation of implicit filters
 *
 * @author Vojtěch Kohout
 */
class ImplicitFilters
{

	/** @var array */
	private $filters;

	/** @var array */
	private $targetedArgs;


	/**
	 * @param array $filters
	 * @param array|null $targetedArgs
	 */
	public function __construct(array $filters, array $targetedArgs = array())
	{
		$this->filters = $filters;
		$this->targetedArgs = $targetedArgs;
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
	public function getTargetedArgs()
	{
		return $this->targetedArgs;
	}

}
