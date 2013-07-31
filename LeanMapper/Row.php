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

use Closure;

/**
 * Pointer to specific position inside LeanMapper\Result instance
 *
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Result */
	private $result;

	/** @var int */
	private $id;


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
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->result->getDataEntry($this->id, $name);
	}

	/**
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->result->setDataEntry($this->id, $name, $value);
	}

	/**
	 * Tells whether Row has given field
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->result->hasDataEntry($this->id, $name);
	}

	/**
	 * Unsets given field
	 *
	 * @param string $name
	 */
	public function __unset($name)
	{
		$this->result->unsetDataEntry($this->id, $name);
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
	 * Returns array of fields with values
	 *
	 * @return array
	 */
	public function getData()
	{
		return $this->result->getData($this->id);
	}

	/**
	 * Returns array of modified fields with new values
	 *
	 * @return array
	 */
	public function getModifiedData()
	{
		return $this->result->getModifiedData($this->id);
	}

	/**
	 * Tells whether row is in modified state
	 *
	 * @return bool
	 */
	public function isModified()
	{
		return $this->result->isModified($this->id);
	}

	/**
	 * Tells whether row is in detached state
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->result->isDetached();
	}

	/**
	 * Detach row (it means mark it as non-persisted)
	 */
	public function detach()
	{
		$data = $this->result->getData($this->id);
		$this->result = Result::getDetachedInstance();
		foreach ($data as $key => $value) {
			$this->result->setDataEntry(0, $key, $value);
		}
		$this->id = 0;
	}

	/**
	 * Marks row as non-updated (isModified() returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->result->markAsUpdated($this->id);
	}

	/**
	 * Marks row as persisted
	 *
	 * @param int $id
	 * @param string $table
	 * @param Connection $connection
	 */
	public function markAsCreated($id, $table, Connection $connection)
	{
		$this->result->markAsCreated($id, $this->id, $table, $connection);
		$this->id = $id;
	}

	/**
	 * Returns referenced LeanMapper\Row instance
	 *
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @return Row|null
	 */
	public function referenced($table, $viaColumn = null, $filters = null, $filterArgs = null)
	{
		return $this->result->getReferencedRow($this->id, $table, $viaColumn, $filters, $filterArgs);
	}

	/**
	 * Returns array of LeanMapper\Row instances referencing current row
	 *
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @param string|null $strategy
	 * @return Row[]
	 */
	public function referencing($table, $viaColumn = null, $filters = null, $filterArgs = null, $strategy = null)
	{
		return $this->result->getReferencingRows($this->id, $table, $viaColumn, $filters, $filterArgs, $strategy);
	}

	/**
	 * @param array $values
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @param string|null $strategy
	 */
	public function addToReferencing(array $values, $table, $viaColumn = null, $filters = null, $filterArgs = null, $strategy = null)
	{
		$this->result->addToReferencing($values, $table, $viaColumn, $filters, $filterArgs, $strategy);
	}

	/**
	 * @param array $values
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @param string|null $strategy
	 */
	public function removeFromReferencing(array $values, $table, $viaColumn = null, $filters = null, $filterArgs = null, $strategy = null)
	{
		$this->result->removeFromReferencing($values, $table, $viaColumn, $filters, $filterArgs, $strategy);
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @param string|null $strategy
	 * @return DataDifference
	 */
	public function createReferencingDataDifference($table, $viaColumn = null, $filters = null, $filterArgs = null, $strategy = null)
	{
		return $this->result->createReferencingDataDifference($table, $viaColumn, $filters, $filterArgs, $strategy);
	}

	/**
	 * Clean in-memory cache of referenced rows
	 *
	 * @param string|null $table
	 * @param string|null $column
	 */
	public function cleanReferencedRowsCache($table = null, $column = null)
	{
		$this->result->cleanReferencedResultsCache($table, $column);
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param string|array|null $filters
	 * @param string|array|null $filterArgs
	 * @param string|null $strategy
	 */
	public function cleanReferencingAddedAndRemovedMeta($table, $viaColumn = null, $filters = null, $filterArgs = null, $strategy = null)
	{
		$this->result->cleanReferencingAddedAndRemovedMeta($table, $viaColumn, $filters, $filterArgs, $strategy);
	}

}