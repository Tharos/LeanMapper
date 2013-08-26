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
 * Has many relationship
 *
 * @author Vojtěch Kohout
 */
class HasMany
{

	/** @var string|null */
	private $columnReferencingSourceTable;

	/** @var string|null */
	private $relationshipTable;

	/** @var string|null */
	private $columnReferencingTargetTable;

	/** @var string|null */
	private $targetTable;

	/** @var string */
	private $strategy;


	/**
	 * @param string|null $columnReferencingSourceTable
	 * @param string|null $relationshipTable
	 * @param string|null $columnReferencingTargetTable
	 * @param string|null $targetTable
	 * @param string $strategy
	 */
	public function __construct($columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable, $targetTable, $strategy)
	{
		$this->columnReferencingSourceTable = $columnReferencingSourceTable;
		$this->relationshipTable = $relationshipTable;
		$this->columnReferencingTargetTable = $columnReferencingTargetTable;
		$this->targetTable = $targetTable;
		$this->strategy = $strategy;
	}

	/**
	 * Gets name of column referencing source table
	 *
	 * @return string|null
	 */
	public function getColumnReferencingSourceTable()
	{
		return $this->columnReferencingSourceTable;
	}

	/**
	 * Gets name of relationship table
	 *
	 * @return string|null
	 */
	public function getRelationshipTable()
	{
		return $this->relationshipTable;
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

	/**
	 * Gets strategy used to get referencing result
	 *
	 * @return string strategy
	 */
	public function getStrategy()
	{
		return $this->strategy;
	}

}
