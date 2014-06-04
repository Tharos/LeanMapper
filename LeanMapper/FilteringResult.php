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

use Closure;
use LeanMapper\Exception\InvalidMethodCallException;

/**
 * @author Vojtěch Kohout
 */
class FilteringResult
{

	/** @var Result */
	private $result;

	/** @var Closure */
	private $validationFunction;


	/**
	 * @param Result $result
	 * @param Closure $validationFunction
	 */
	public function __construct(Result $result, Closure $validationFunction = null)
	{
		$this->result = $result;
		$this->validationFunction = $validationFunction;
	}

	/**
	 * @return Result
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @return Closure
	 * @throws InvalidMethodCallException
	 */
	public function getValidationFunction()
	{
		if ($this->validationFunction === null) {
			throw new InvalidMethodCallException("FilteringResult doesn't have validation function.");
		}
		return $this->validationFunction;
	}

	/**
	 * @return bool
	 */
	public function hasValidationFunction()
	{
		return $this->validationFunction !== null;
	}

}
