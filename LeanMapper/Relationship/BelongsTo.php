<?php

namespace LeanMapper\Relationship;

/**
 * @author Vojtěch Kohout
 */
abstract class BelongsTo
{

	/** @var string */
	private $sourceTable;

	/** @var string */
	private $columnReferencingSourceTable;

	/** @var string */
	private $targetTable;


	/**
	 * @param string $sourceTable
	 * @param string $columnReferencingSourceTable
	 * @param string $targetTable
	 */
	public function __construct($sourceTable, $columnReferencingSourceTable, $targetTable)
	{
		$this->sourceTable = $sourceTable;
		$this->columnReferencingSourceTable = $columnReferencingSourceTable;
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
	public function getTargetTable()
	{
		return $this->targetTable;
	}

}