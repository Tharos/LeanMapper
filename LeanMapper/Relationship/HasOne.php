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

	/** @var string|null */
	private $columnReferencingTargetTable;

	/** @var string|null */
	private $targetTable;


	/**
	 * @param string|null $columnReferencingTargetTable
	 * @param string|null $targetTable
	 */
	public function __construct($columnReferencingTargetTable, $targetTable)
	{
		$this->columnReferencingTargetTable = $columnReferencingTargetTable;
		$this->targetTable = $targetTable;
	}

	/**
	 * Gets name of column referencing target table
	 *
	 * @return string|null
	 */
	public function getColumnReferencingTargetTable()
	{
		return $this->columnReferencingTargetTable;
	}

	/**
	 * Gets name of target table
	 *
	 * @return string|null
	 */
	public function getTargetTable()
	{
		return $this->targetTable;
	}

}
