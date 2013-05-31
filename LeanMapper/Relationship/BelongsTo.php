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
 * Base class for belongs to relationships
 *
 * @author Vojtěch Kohout
 */
abstract class BelongsTo
{

	/** @var string */
	private $columnReferencingSourceTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $columnReferencingSourceTable
	 * @param string $targetTable
	 */
	public function __construct($columnReferencingSourceTable, $targetTable)
	{
		$this->columnReferencingSourceTable = $columnReferencingSourceTable;
		$this->targetTable = $targetTable;
	}

	/**
	 * Returns name of column referencing source table
	 *
	 * @return string
	 */
	public function getColumnReferencingSourceTable()
	{
		return $this->columnReferencingSourceTable;
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