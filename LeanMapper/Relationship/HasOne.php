<?php

namespace LeanMapper\Relationship;

/**
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