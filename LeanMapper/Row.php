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
use DibiConnection;

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
		return $this->result->isDetached($this->id);
	}

	/**
	 * Marks row as detached (it means non-persisted)
	 */
	public function detach()
	{
		$this->result->detach($this->id);
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
	 * @param DibiConnection $connection
	 */
	public function markAsCreated($id, $table, DibiConnection $connection)
	{
		$this->result->markAsCreated($id, $this->id, $table, $connection);
		$this->id = $id;
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
	 * Returns referenced LeanMapper\Row instance
	 *
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @return Row|null
	 */
	public function referenced($table, Closure $filter = null, $viaColumn = null)
	{
		return $this->result->getReferencedRow($this->id, $table, $filter, $viaColumn);
	}

	/**
	 * Returns array of LeanMapper\Row instances referencing current row
	 *
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 * @return Row[]
	 */
	public function referencing($table, Closure $filter = null, $viaColumn = null, $strategy = null)
	{
		return $this->result->getReferencingRows($this->id, $table, $filter, $viaColumn, $strategy);
	}

}