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
class ResultProxy
{

	/** @var Result */
	private $result;


	/**
	 * @param Result $result
	 */
	public function __construct(Result $result)
	{
		$this->result = $result;
	}

	/**
	 * @param Result $referencedResult
	 * @param string $table
	 * @param string $viaColumn
	 */
	public function setReferencedResult(Result $referencedResult, $table, $viaColumn = null)
	{
		$this->result->setReferencedResult($referencedResult, $table, $viaColumn);
	}

	/**
	 * @param Result $referencingResult
	 * @param string $table
	 * @param string $viaColumn
	 * @param string $strategy
	 */
	public function setReferencingResult(Result $referencingResult, $table, $viaColumn = null, $strategy = Result::STRATEGY_IN)
	{
		$this->setReferencingResult($referencingResult, $table, $viaColumn, $strategy);
	}

}
