<?php

namespace LeanMapper\Relationship;

/**
 * @author Vojtěch Kohout
 */
class HasMany
{

	/** @var string */
	private $sourceTable;

	/** @var string */
	private $columnReferencingSourceTable;

	/** @var string */
	private $relationshipTable;

	/** @var string */
	private $columnReferencingTargetTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $sourceTable
	 * @param string $columnReferencingSourceTable
	 * @param string $relationshipTable
	 * @param string $columnReferencingTargetTable
	 * @param string $targetTable
	 */
	public function __construct($sourceTable, $columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable, $targetTable)
	{
		$this->sourceTable = $sourceTable;
		$this->columnReferencingSourceTable = $columnReferencingSourceTable;
		$this->relationshipTable = $relationshipTable;
		$this->columnReferencingTargetTable = $columnReferencingTargetTable;
		$this->targetTable = $targetTable;
	}

	/**
	 * @return string
	 */
	public function getSourceTable()
	{
		return $this->sourceTable;
	}

	/**
	 * @return string
	 */
	public function getColumnReferencingSourceTable()
	{
		return $this->columnReferencingSourceTable;
	}

	/**
	 * @return string
	 */
	public function getRelationshipTable()
	{
		return $this->relationshipTable;
	}

	/**
	 * @return string
	 */
	public function getColumnReferencingTargetTable()
	{
		return $this->columnReferencingTargetTable;
	}

	/**
	 * @return string
	 */
	public function getTargetTable()
	{
		return $this->targetTable;
	}

}