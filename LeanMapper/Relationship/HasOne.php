<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Relationship;

/**
 * Has one relationship
 *
 * @author Vojtěch Kohout
 */
class HasOne
{

	/** @var string */
	private $columnReferencingTargetTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $columnReferencingTargetTable
	 * @param string $targetTable
	 */
	public function __construct($columnReferencingTargetTable, $targetTable)
	{
		$this->columnReferencingTargetTable = $columnReferencingTargetTable;
		$this->targetTable = $targetTable;
	}

	/**
	 * Returns name of column referencing target table
	 *
	 * @return string
	 */
	public function getColumnReferencingTargetTable()
	{
		return $this->columnReferencingTargetTable;
	}

	/**
	 * Returns name of target table
	 *
	 * @return string
	 */
	public function getTargetTable()
	{
		return $this->targetTable;
	}

}