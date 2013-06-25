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
use DibiFluent;
use DibiSqliteDriver;
use DibiSqlite3Driver;
use DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;

/**
 * Set of related data
 *
 * @author Vojtěch Kohout
 */
class Result implements \Iterator
{

	const STRATEGY_IN = 'in';

	const STRATEGY_UNION = 'union';

	/** @var array */
	private $data;

	/** @var array */
	private $modified = array();

	/** @var array */
	private $added = array();

	/** @var array */
	private $removed = array();

	/** @var string */
	private $table;

	/** @var DibiConnection */
	private $connection;

	/** @var IMapper */
	protected $mapper;

	/** @var array */
	private $keys;

	/** @var self[] */
	private $referenced = array();

	/** @var self[] */
	private $referencing = array();


	/**
	 * Creates new common instance (it means persisted)
	 *
	 * @param DibiRow|DibiRow[] $data
	 * @param string $table
	 * @param DibiConnection $connection
	 * @param IMapper $mapper
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function getInstance($data, $table, DibiConnection $connection, IMapper $mapper)
	{
		$dataArray = array();
		$primaryKey = $mapper->getPrimaryKey($table);
		if ($data instanceof DibiRow) {
			$dataArray = array(isset($data->$primaryKey) ? $data->$primaryKey : 0 => $data->toArray());
		} else {
			$e = new InvalidArgumentException('Invalid type of data given, only DibiRow or array of DibiRow is supported at this moment.');
			if (is_array($data)) {
				foreach ($data as $record) {
					if (!($record instanceof DibiRow)) {
						throw $e;
					}
					if (isset($record->$primaryKey)) {
						$dataArray[$record->$primaryKey] = $record->toArray();
					} else {
						$dataArray[] = $record->toArray();
					}
				}
			} else {
				throw $e;
			}
		}
		return new self($dataArray, $table, $connection, $mapper);
	}

	/**
	 * Creates new detached instance (it means non-persisted)
	 *
	 * @return self
	 */
	public static function getDetachedInstance()
	{
		return new self;
	}

	/**
	 * @param IMapper $mapper
	 */
	public function setMapper(IMapper $mapper)
	{
		$this->mapper = $mapper;
	}

	/**
	 * @return IMapper|null
	 */
	public function getMapper()
	{
		return $this->mapper;
	}

	/**
	 * Creates new LeanMapper\Row instance pointing to requested row in LeanMapper\Result
	 *
	 * @param int $id
	 * @return Row|null
	 */
	public function getRow($id = 0)
	{
		if (!isset($this->data[$id])) {
			return null;
		}
		return new Row($this, $id);
	}

	/**
	 * Returns value of given field from row with given id
	 *
	 * @param mixed $id
	 * @param string $key
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function getDataEntry($id, $key)
	{
		if (!isset($this->data[$id]) or !array_key_exists($key, $this->data[$id])) {
			throw new InvalidArgumentException("Missing '$key' value for requested row.");
		}
		return $this->data[$id][$key];
	}

	/**
	 * Sets value of given field in row with given id
	 *
	 * @param mixed $id
	 * @param string $key
	 * @param mixed $value
	 * @throws InvalidArgumentException
	 */
	public function setDataEntry($id, $key, $value)
	{
		if (!isset($this->data[$id])) {
			throw new InvalidArgumentException("Missing row with ID $id.");
		}
		if (!$this->isDetached() and $key === $this->mapper->getPrimaryKey($this->table)) { // mapper is always set when Result is not detached
			throw new InvalidArgumentException("ID can only be set in detached rows.");
		}
		$this->modified[$id][$key] = true;
		$this->data[$id][$key] = $value;
	}

	/**
	 * Tells whether row with given id has given field
	 *
	 * @param mixed $id
	 * @param string $key
	 * @return bool
	 */
	public function hasDataEntry($id, $key)
	{
		return isset($this->data[$id]) and array_key_exists($key, $this->data[$id]);
	}

	/**
	 * Unset given field in row with given id
	 *
	 * @param mixed $id
	 * @param string $key
	 * @throws InvalidArgumentException
	 */
	public function unsetDataEntry($id, $key)
	{
		if (!isset($this->data[$id])) {
			throw new InvalidArgumentException("Missing row with ID $id.");
		}
		unset($this->data[$id][$key], $this->modified[$id][$key]);
	}

	/**
	 * @param array $values
	 */
	public function addDataEntry(array $values)
	{
		$this->data[] = $values;
		$this->added[] = $values;
		$this->cleanReferencedResultsCache();
	}

	/**
	 * @param array $values
	 * @throws InvalidArgumentException
	 */
	public function removeDataEntry(array $values)
	{
		foreach ($this->data as $key => $entry) {
			if ($values === array_intersect_assoc($entry, $values)) {
				$this->removed[] = $entry;
				unset($this->data[$key], $this->modified[$key]);
				break;
			}
		}
	}

	/**
	 * Returns array of fields and values of requested row
	 *
	 * @param int $id
	 * @return array
	 */
	public function getData($id)
	{
		return isset($this->data[$id]) ? $this->data[$id] : array();
	}

	/**
	 * Returns array of modified fields and values of requested row
	 *
	 * @param int $id
	 * @return array
	 */
	public function getModifiedData($id)
	{
		$result = array();
		if (isset($this->modified[$id])) {
			foreach (array_keys($this->modified[$id]) as $field) {
				$result[$field] = $this->data[$id][$field];
			}
		}
		return $result;
	}

	/**
	 * @return DataDifference
	 */
	public function createDataDifference()
	{
		return new DataDifference($this->added, $this->removed);
	}

	/**
	 * Tells whether requested row is in modified state
	 *
	 * @param int $id
	 * @return bool
	 */
	public function isModified($id)
	{
		return isset($this->modified[$id]) and !empty($this->modified[$id]);
	}

	/**
	 * Tells whether result is in detached state (in means non-persisted)
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->connection === null or $this->table === null or $this->mapper === null;
	}

	/**
	 * Marks requested row as non-updated (isModified($id) returns false right after this method call)
	 *
	 * @param int $id
	 * @throws InvalidMethodCallException
	 */
	public function markAsUpdated($id)
	{
		if ($this->isDetached()) {
			throw new InvalidMethodCallException('Detached result cannot be marked as updated.');
		}
		if (isset($this->modified[$id])) {
			unset($this->modified[$id]);
		}
	}

	/**
	 * Marks requested row as persisted
	 *
	 * @param mixed $newId
	 * @param mixed $oldId
	 * @param string $table
	 * @param DibiConnection $connection
	 * @throws InvalidStateException
	 */
	public function markAsCreated($newId, $oldId, $table, DibiConnection $connection)
	{
		if (!$this->isDetached()) {
			throw new InvalidStateException('Result is not in detached state.');
		}
		if ($this->mapper === null) {
			throw new InvalidStateException('Missing mapper.');
		}
		$modifiedData = $this->getModifiedData($oldId);
		unset($this->data[$oldId]);
		$this->data[$newId] = array($this->mapper->getPrimaryKey($table) => $newId) + $modifiedData;
		foreach (array($newId, $oldId) as $key) {
			unset($this->modified[$key]);
		}
		$this->table = $table;
		$this->connection = $connection;
	}

	public function cleanAddedAndRemovedMeta()
	{
		$this->added = array();
		$this->removed = array();
	}

	/**
	 * Creates new LeanMapper\Row instance pointing to requested row in referenced result
	 *
	 * @param int $id
	 * @param string $table
	 * @param callable|null $filter
	 * @param string|null $viaColumn
	 * @throws InvalidStateException
	 * @return Row|null
	 */
	public function getReferencedRow($id, $table, Closure $filter = null, $viaColumn = null)
	{
		$result = $this->getReferencedResult($table, $filter, $viaColumn);
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($this->table, $table);
		}
		return $result->getRow($this->getDataEntry($id, $viaColumn));
	}

	/**
	 * Creates new array of LeanMapper\Row instances pointing to requested row in referencing result
	 *
	 * @param int $id
	 * @param string $table
	 * @param callable|null $filter
	 * @param string|null $viaColumn
	 * @param string $strategy
	 * @throws InvalidStateException
	 * @return Row[]
	 */
	public function getReferencingRows($id, $table, Closure $filter = null, $viaColumn = null, $strategy = null)
	{
		$collection = $this->getReferencingResult($table, $filter, $viaColumn, $strategy);
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
		}
		$rows = array();
		foreach ($collection as $key => $row) {
			if ($row[$viaColumn] === $id) {
				$rows[] = new Row($collection, $key);
			}
		}
		return $rows;
	}

	/**
	 * @param array $values
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 */
	public function addToReferencing(array $values, $table, Closure $filter = null, $viaColumn = null, $strategy = self::STRATEGY_IN)
	{
		$this->getReferencingResult($table, $filter, $viaColumn, $strategy)
				->addDataEntry($values);
	}

	/**
	 * @param array $values
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 */
	public function removeFromReferencing(array $values, $table, Closure $filter = null, $viaColumn = null, $strategy = self::STRATEGY_IN)
	{
		$this->getReferencingResult($table, $filter, $viaColumn, $strategy)
				->removeDataEntry($values);
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 * @return DataDifference
	 */
	public function createReferencingDataDifference($table, Closure $filter = null, $viaColumn = null, $strategy = self::STRATEGY_IN)
	{
		return $this->getReferencingResult($table, $filter, $viaColumn, $strategy)
				->createDataDifference();
	}

	/**
	 * Clean in-memory cache of referenced results
	 *
	 * @param string|null $table
	 * @param string|null $column
	 */
	public function cleanReferencedResultsCache($table = null, $column = null)
	{
		if ($table === null or $column === null) {
			$this->referenced = array();
		} else {
			foreach ($this->referenced as $key => $value) {
				if (preg_match("~^$table\\($column\\)(#.*)?$~", $key)) {
					unset($this->referenced[$key]);
				}
			}
		}
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @param string|null $strategy
	 */
	public function cleanReferencingAddedAndRemovedMeta($table, Closure $filter = null, $viaColumn = null, $strategy = self::STRATEGY_IN)
	{
		$this->getReferencingResult($table, $filter, $viaColumn, $strategy)
				->cleanAddedAndRemovedMeta();
	}

	//========== interface \Iterator ====================

	/**
	 * @return mixed
	 */
	public function current()
	{
		$key = current($this->keys);
		return $this->data[$key];
	}

	public function next()
	{
		next($this->keys);
	}

	/**
	 * @return int
	 */
	public function key()
	{
		return current($this->keys);
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		return current($this->keys) !== false;
	}

	public function rewind()
	{
		$this->keys = array_keys($this->data);
		reset($this->keys);
	}

	////////////////////
	////////////////////

	/**
	 * @param array|null $data
	 * @param string|null $table
	 * @param DibiConnection|null $connection
	 * @param IMapper|null $mapper
	 */
	private function __construct(array $data = null, $table = null, DibiConnection $connection = null, IMapper $mapper = null)
	{
		if ($data === null) {
			$data = array(array());
		}
		$this->data = $data;
		$this->table = $table;
		$this->connection = $connection;
		$this->mapper = $mapper;
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string $viaColumn
	 * @throws InvalidStateException
	 * @return self
	 */
	private function getReferencedResult($table, Closure $filter = null, $viaColumn = null)
	{
		if ($this->isDetached()) {
			throw new InvalidStateException('Cannot get referenced result for detached result.');
		}
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($this->table, $table);
		}
		$key = "$table($viaColumn)";
		$primaryKey = $this->mapper->getPrimaryKey($table);
		if ($filter === null) {
			if (!isset($this->referenced[$key])) {
				$data = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $primaryKey, $this->extractIds($viaColumn))
						->fetchAll();
				$this->referenced[$key] = self::getInstance($data, $table, $this->connection, $this->mapper);
			}
		} else {
			$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $primaryKey, $this->extractIds($viaColumn));
			$filter($statement);

			$sql = (string) $statement;
			$key .= '#' . md5($sql);

			if (!isset($this->referenced[$key])) {
				$this->referenced[$key] = self::getInstance($this->connection->query($sql)->fetchAll(), $table, $this->connection, $this->mapper);
			}
		}
		return $this->referenced[$key];
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string $viaColumn
	 * @param string $strategy
	 * @throws InvalidStateException
	 * @return self
	 */
	private function getReferencingResult($table, Closure $filter = null, $viaColumn = null, $strategy = self::STRATEGY_IN)
	{
		$strategy = $this->translateStrategy($strategy);
		if ($this->isDetached()) {
			throw new InvalidStateException('Cannot get referencing rows for detached result.');
		}
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
		}
		$key = "$table($viaColumn)$strategy";
		$primaryKey = $this->mapper->getPrimaryKey($this->table);
		if ($strategy === self::STRATEGY_IN) {
			if ($filter === null) {
				if (!isset($this->referencing[$key])) {
					$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $viaColumn, $this->extractIds($primaryKey));
					$this->referencing[$key] = self::getInstance($statement->fetchAll(), $table, $this->connection, $this->mapper);
				}
			} else {
				$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $viaColumn, $this->extractIds($primaryKey));
				$filter($statement);

				$sql = (string) $statement;
				$key .= '#' . md5($sql);

				if (!isset($this->referencing[$key])) {
					$this->referencing[$key] = self::getInstance($this->connection->query($sql)->fetchAll(), $table, $this->connection, $this->mapper);
				}
			}
		} else { // self::STRATEGY_UNION
			if ($filter === null) {
				if (!isset($this->referencing[$key])) {
					$ids = $this->extractIds($primaryKey);
					if (count($ids) === 0) {
						$data = array();
					} else {
						$data = $this->connection->query(
							$this->buildUnionStrategySql($ids, $table, $viaColumn)
						)->fetchAll();
					}
					$this->referencing[$key] = self::getInstance($data, $table, $this->connection, $this->mapper);
				}
			} else {
				$ids = $this->extractIds($primaryKey);
				if (count($ids) === 0) {
					$this->referencing[$key] = self::getInstance(array(), $table, $this->connection, $this->mapper);
				} else {
					$sql = $this->buildUnionStrategySql($ids, $table, $viaColumn, $filter);
					$key .= '#' . md5($sql);
					if (!isset($this->referencing[$key])) {
						$this->referencing[$key] = self::getInstance($this->connection->query($sql)->fetchAll(), $table, $this->connection, $this->mapper);
					}
				}
			}
		}
		return $this->referencing[$key];
	}

	/**
	 * @param string $column
	 * @return array
	 */
	private function extractIds($column)
	{
		$ids = array();
		foreach ($this->data as $data) {
			if ($data[$column] === null) continue;
			$ids[$data[$column]] = true;
		}
		return array_keys($ids);
	}

	/**
	 * @param array $ids
	 * @param string $table
	 * @param string $viaColumn
	 * @param Closure|null $filter
	 * @return string
	 */
	private function buildUnionStrategySql(array $ids, $table, $viaColumn, Closure $filter = null)
	{
		$statement = $this->createTableSelection($table)->where('%n.%n = %i', $table, $viaColumn, array_shift($ids));
		if ($filter !== null) {
			$filter($statement);
		}
		while ($id = array_shift($ids)) {
			$tempStatement = $this->createTableSelection($table)->where('%n.%n = %i', $table, $viaColumn, $id);
			if ($filter !== null) {
				$filter($tempStatement);
			}
			$statement->union($tempStatement);
		}
		$sql = (string) $statement;

		$driver = $this->connection->getDriver();
		// now we have to fix wrongly generated SQL by dibi...
		if ($driver instanceof DibiSqliteDriver or $driver instanceof DibiSqlite3Driver) {
			$sql = preg_replace('#(?<=UNION )\((SELECT.*?)\)(?= UNION|$)#', '$1', $sql); // (...) UNION (...) to ... UNION ...
		} else {
			$sql = preg_replace('#^(SELECT.*?)(?= UNION)#', '($1)', $sql); // ... UNION (...) to (...) UNION (...)
		}
		return $sql;
	}

	/**
	 * @param string $table
	 * @return DibiFluent
	 */
	private function createTableSelection($table)
	{
		return $this->connection->select('%n.*', $table)->from($table);
	}

	/**
	 * @param string|null $strategy
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function translateStrategy($strategy)
	{
		if ($strategy === null) {
			$strategy = self::STRATEGY_IN;
		} else {
			if ($strategy !== self::STRATEGY_IN and $strategy !== self::STRATEGY_UNION) {
				throw new InvalidArgumentException("Unsupported SQL strategy given: $strategy.");
			}
		}
		return $strategy;
	}

}