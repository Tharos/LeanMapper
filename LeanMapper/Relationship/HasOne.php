<?php

namespace LeanMapper\Relationship;

/**
 * @author Vojtěch Kohout
 */
class HasOne
{

	/** @var string */
	private $sourceTable;

	/** @var string */
	private $columnReferencingTargetTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $sourceTable
	 * @param string $columnReferencingTargetTable
	 * @param string $targetTable
	 */
	public function __construct($sourceTable, $columnReferencingTargetTable, $targetTable)
	{
		$this->sourceTable = $sourceTable;
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