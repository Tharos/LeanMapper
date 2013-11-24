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
 * Pointer to specific position within Result instance
 *
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Result */
	private $result;

	/** @var int */
	private $id;

	/** @var array */
	private $referencedRows;


	/**
	 * @param Result $result
	 * @param int $id
	 */
	public function __construct(Result $result, $id)
	{
		$this->result = $result;
		$this->id = $id;
	}

	/**
	 * Gets value of given column
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->result->getDataEntry($this->id, $name);
	}

	/**
	 * Sets value of given column
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->result->setDataEntry($this->id, $name, $value);
	}

	/**
	 * Tells whether Row has given column and is not null
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->hasColumn($name) and $this->$name !== null;
	}

	/**
	 * Tells whether Row has given column
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasColumn($name)
	{
		return $this->result->hasDataEntry($this->id, $name);
	}

	/**
	 * Unsets given column
	 *
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->result->unsetDataEntry($this->id, $name);
	}

	/**
	 * @param Connection $connection
	 */
	public function setConnection(Connection $connection)
	{
		$this->result->setConnection($connection);
	}

	/**
	 * @return bool
	 */
	public function hasConnection()
	{
		return $this->result->hasConnection();
	}

	/**
	 * @param IMapper $mapper
	 */
	public function setMapper(IMapper $mapper)
	{
		$this->result->setMapper($mapper);
	}

	/**
	 * @return IMapper|null
	 */
	public function getMapper()
	{
		return $this->result->getMapper();
	}

	/**
	 * Returns values of columns
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->result->getData($this->id);
	}

	/**
	 * Returns values of columns that were modified
	 *
	 * @return array
	 */
	public function getModifiedData()
	{
		return $this->result->getModifiedData($this->id);
	}

	/**
	 * Tells whether Row is in modified state
	 *
	 * @return bool
	 */
	public function isModified()
	{
		return $this->result->isModified($this->id);
	}

	/**
	 * Tells whether Row is in detached state
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->result->isDetached();
	}

	/**
	 * Detaches Row (it means mark it as non-persisted)
	 */
	public function detach()
	{
		$data = $this->result->getData($this->id);
		$this->result = Result::createDetachedInstance();
		foreach ($data as $key => $value) {
			$this->result->setDataEntry(Result::DETACHED_ROW_ID, $key, $value);
		}
		$this->id = Result::DETACHED_ROW_ID;
	}

	/**
	 * Marks Row as attached
	 *
	 * @param int $id
	 * @param string $table
	 */
	public function attach($id, $table)
	{
		$this->result->attach($id, $table);
		$this->id = $id;
	}

	/**
	 * Marks Row as non-modified (isModified returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->result->markAsUpdated($this->id);
	}

	/**
	 * Gets referenced Row instance
	 *
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @return Row|null
	 */
	public function referenced($table, $viaColumn = null, Filtering $filtering = null)
	{
		if (isset($this->referencedRows[$viaColumn])) {
			return $this->referencedRows[$viaColumn];
		}
		return $this->result->getReferencedRow($this->id, $table, $viaColumn, $filtering);
	}

	/**
	 * Gets array of Row instances referencing current Row
	 *
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 * @return Row[]
	 */
	public function referencing($table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		return $this->result->getReferencingRows($this->id, $table, $viaColumn, $filtering, $strategy);
	}

	/**
	 * @param Row $row
	 * @param string $viaColumn
	 */
	public function setReferencedRow(self $row, $viaColumn)
	{
		$this->referencedRows[$viaColumn] = $row;
	}

	/**
	 * Adds new data entry to referencing Result
	 *
	 * @param array $values
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 */
	public function addToReferencing(array $values, $table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		$this->result->addToReferencing($values, $table, $viaColumn, $filtering, $strategy);
	}

	/**
	 * Remove given data entry from referencing Result
	 *
	 * @param array $values
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 */
	public function removeFromReferencing(array $values, $table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		$this->result->removeFromReferencing($values, $table, $viaColumn, $filtering, $strategy);
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 * @return DataDifference
	 */
	public function createReferencingDataDifference($table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		return $this->result->createReferencingDataDifference($table, $viaColumn, $filtering, $strategy);
	}

	/**
	 * Cleans in-memory cache with referenced rows
	 *
	 * @param string|null $table
	 * @param string|null $viaColumn
	 */
	public function cleanReferencedRowsCache($table = null, $viaColumn = null)
	{
		$this->result->cleanReferencedResultsCache($table, $viaColumn);
	}

	/**
	 * Cleans in-memory cache with referencing rows
	 *
	 * @param string|null $table
	 * @param string|null $viaColumn
	 */
	public function cleanReferencingRowsCache($table = null, $viaColumn = null)
	{
		$this->result->cleanReferencingResultsCache($table, $viaColumn);
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 */
	public function cleanReferencingAddedAndRemovedMeta($table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		$this->result->cleanReferencingAddedAndRemovedMeta($table, $viaColumn, $filtering, $strategy);
	}

}
