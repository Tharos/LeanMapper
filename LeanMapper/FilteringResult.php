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
 * @author Vojtěch Kohout
 */
class FilteringResult
{

	/** @var Result */
	private $result;

	/** @var array */
	private $arguments;

	/**
	 * @param Result $result
	 * @param array $arguments
	 */
	public function __construct(Result $result, array $arguments)
	{
		$this->result = $result;
		$this->arguments = $arguments;
	}

	/**
	 * @return array
	 */
	public function getArguments()
	{
		return $this->arguments;
	}


	/**
	 * @return Result
	 */
	public function getResult()
	{
		return $this->result;
	}

}
