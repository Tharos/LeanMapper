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
use DibiSqliteDriver;
use DibiSqlite3Driver;
use DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;

/**
 * Set of related data, heart of Lean Mapper
 *
 * @author Vojtěch Kohout
 */
class Result implements \Iterator
{

	const STRATEGY_IN = 'in';

	const STRATEGY_UNION = 'union';

	const DETACHED_ROW_ID = -1;

	/** @var bool */
	private $isDetached;

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

	/** @var Connection */
	private $connection;

	/** @var IMapper */
	protected $mapper;

	/** @var array */
	private $keys;

	/** @var self[] */
	private $referenced = array();

	/** @var self[] */
	private $referencing = array();

	/** @var string|null */
	private $originKey;

	/** @var array */
	private $index = array();


	/**
	 * Creates new common instance (it means persisted)
	 *
	 * @param DibiRow|DibiRow[] $data
	 * @param string $table
	 * @param Connection $connection
	 * @param IMapper $mapper
	 * @param string|null $originKey
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public static function createInstance($data, $table, Connection $connection, IMapper $mapper, $originKey = null)
	{
		$dataArray = array();
		$primaryKey = $mapper->getPrimaryKey($table);
		if ($data instanceof DibiRow) {
			$dataArray = array(isset($data->$primaryKey) ? $data->$primaryKey : self::DETACHED_ROW_ID => $data->toArray());
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
		return new self($dataArray, $table, $connection, $mapper, $originKey);
	}

	/**
	 * Creates new detached instance (it means non-persisted)
	 *
	 * @return self
	 */
	public static function createDetachedInstance()
	{
		return new self;
	}

	/**
	 * @param Connection $connection
	 * @throws InvalidStateException
	 */
	public function setConnection(Connection $connection)
	{
		if ($this->connection === null) {
			$this->connection = $connection;
		} elseif ($this->connection !== $connection) {
			throw new InvalidStateException("Given connection doesn't equal to connection already present in Result.");
		}
	}

	/**
	 * @return bool
	 */
	public function hasConnection()
	{
		return $this->connection !== null;
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
	 * @return string|null
	 */
	public function getOriginKey()
	{
		return $this->originKey;
	}

	/**
	 * Creates new Row instance pointing to specific row within Result
	 *
	 * @param int $id
	 * @throws InvalidArgumentException
	 * @return Row|null
	 */
	public function getRow($id = null)
	{
		if ($this->isDetached) {
			if ($id !== null) {
				throw new InvalidArgumentException('Argument $id in Result::getRow method cannot be passed when Result is in detached state.');
			}
			$id = self::DETACHED_ROW_ID;
		} elseif ($id === null) {
			throw new InvalidArgumentException('Argument $id in Result::getRow method must be passed when Result is in attached state.');
		}
		if (!isset($this->data[$id])) {
			return null;
		}
		return new Row($this, $id);
	}

	/**
	 * Gets value of given column from row with given id
	 *
	 * @param mixed $id
	 * @param string $key
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function getDataEntry($id, $key)
	{
		if (!isset($this->data[$id])) {
			throw new InvalidArgumentException("Missing row with id $id.");
		}
		if (!array_key_exists($key, $this->data[$id])) {
			throw new InvalidArgumentException("Missing '$key' column in row with id $id.");
		}
		return $this->data[$id][$key];
	}

	/**
	 * Sets value of given column in row with given id
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
		if (!$this->isDetached and $key === $this->mapper->getPrimaryKey($this->table)) { // mapper is always set when Result is not detached
			throw new InvalidArgumentException("ID can only be set in detached rows.");
		}
		$this->modified[$id][$key] = true;
		$this->data[$id][$key] = $value;
	}

	/**
	 * Tells whether row with given id has given column
	 *
	 * @param mixed $id
	 * @param string $column
	 * @return bool
	 */
	public function hasDataEntry($id, $column)
	{
		return isset($this->data[$id]) and array_key_exists($column, $this->data[$id]);
	}

	/**
	 * Unsets given column in row with given id
	 *
	 * @param mixed $id
	 * @param string $column
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 */
	public function unsetDataEntry($id, $column)
	{
		if (!isset($this->data[$id])) {
			throw new InvalidArgumentException("Missing row with ID $id.");
		}
		unset($this->data[$id][$column], $this->modified[$id][$column]);
	}

	/**
	 * Adds new data entry
	 *
	 * @param array $values
	 * @throws InvalidStateException
	 */
	public function addDataEntry(array $values)
	{
		if ($this->isDetached) {
			throw new InvalidStateException('Cannot add data entry to detached Result.');
		}
		$this->data[] = $values;
		$this->added[] = $values;
		$this->cleanReferencedResultsCache();
	}

	/**
	 * Removes given data entry
	 *
	 * @param array $values
	 * @throws InvalidStateException
	 */
	public function removeDataEntry(array $values)
	{
		if ($this->isDetached) {
			throw new InvalidStateException('Cannot remove data entry to detached Result.');
		}
		foreach ($this->data as $key => $entry) {
			if (array_diff_assoc($values, $entry) === array()) {
				$this->removed[] = $entry;
				unset($this->data[$key], $this->modified[$key]);
				break;
			}
		}
	}

	/**
	 * Returns values of columns of requested row
	 *
	 * @param int $id
	 * @return array
	 */
	public function getData($id)
	{
		return isset($this->data[$id]) ? $this->data[$id] : array();
	}

	/**
	 * Returns values of columns of requested row that were modified
	 *
	 * @param int $id
	 * @return array
	 */
	public function getModifiedData($id)
	{
		$result = array();
		if (isset($this->modified[$id])) {
			foreach (array_keys($this->modified[$id]) as $column) {
				$result[$column] = $this->data[$id][$column];
			}
		}
		return $result;
	}

	/**
	 * Creates new DataDifference instance relevant to current Result state
	 *
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
	 * Tells whether Result is in detached state (in means non-persisted)
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->isDetached;
	}

	/**
	 * Marks requested row as non-modified (isModified returns false right after this method call)
	 *
	 * @param int $id
	 * @throws InvalidMethodCallException
	 */
	public function markAsUpdated($id)
	{
		if ($this->isDetached) {
			throw new InvalidMethodCallException('Detached Result cannot be marked as updated.');
		}
		unset($this->modified[$id]);
	}

	/**
	 * @param mixed $id
	 * @param string $table
	 * @throws InvalidStateException
	 */
	public function attach($id, $table)
	{
		if (!$this->isDetached) {
			throw new InvalidStateException('Result is not in detached state.');
		}
		if ($this->connection === null) {
			throw new InvalidStateException('Missing connection.');
		}
		if ($this->mapper === null) {
			throw new InvalidStateException('Missing mapper.');
		}
		$modifiedData = $this->getModifiedData(self::DETACHED_ROW_ID);
		$this->data = array(
			$id => array($this->mapper->getPrimaryKey($table) => $id) + $modifiedData
		);
		$this->modified = array();
		$this->table = $table;
		$this->isDetached = false;
	}

	public function cleanAddedAndRemovedMeta()
	{
		$this->added = array();
		$this->removed = array();
	}

	/**
	 * Creates new Row instance pointing to requested row in referenced Result
	 *
	 * @param int $id
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @throws InvalidStateException
	 * @return Row|null
	 */
	public function getReferencedRow($id, $table, $viaColumn = null, Filtering $filtering = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($this->table, $table);
		}
		$result = $this->getReferencedResult($table, $viaColumn, $filtering);
		$rowId = $this->getDataEntry($id, $viaColumn);
		return $rowId === null ? null : $result->getRow($rowId);
	}

	/**
	 * Creates new array of Row instances pointing to requested row in referencing Result
	 *
	 * @param int $id
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string $strategy
	 * @throws InvalidStateException
	 * @return Row[]
	 */
	public function getReferencingRows($id, $table, $viaColumn = null, Filtering $filtering = null, $strategy = null)
	{
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
		}
		$referencingResult = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);
		$originKey = $referencingResult->getOriginKey();
		if (!isset($this->index[$originKey])) {
			$this->index[$originKey] = array();
			foreach ($referencingResult as $key => $row) {
				$this->index[$originKey][$row[$viaColumn]][] = new Row($referencingResult, $key);
			}
		}
		if (!isset($this->index[$originKey][$id])) {
			return array();
		}
		return $this->index[$originKey][$id];
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
	public function addToReferencing(array $values, $table, $viaColumn = null, Filtering $filtering = null, $strategy = self::STRATEGY_IN)
	{
		$result = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);
		$result->addDataEntry($values);
		unset($this->index[$result->getOriginKey()]);
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
	public function removeFromReferencing(array $values, $table, $viaColumn = null, Filtering $filtering = null, $strategy = self::STRATEGY_IN)
	{
		$result = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);
		$result->removeDataEntry($values);
		unset($this->index[$result->getOriginKey()]);
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 * @return DataDifference
	 */
	public function createReferencingDataDifference($table, $viaColumn = null, Filtering $filtering = null, $strategy = self::STRATEGY_IN)
	{
		return $this->getReferencingResult($table, $viaColumn, $filtering, $strategy)
				->createDataDifference();
	}

	/**
	 * Cleans in-memory cache with referenced results
	 *
	 * @param string|null $table
	 * @param string|null $viaColumn
	 */
	public function cleanReferencedResultsCache($table = null, $viaColumn = null)
	{
		if ($table === null or $viaColumn === null) {
			$this->referenced = array();
		} else {
			foreach ($this->referenced as $key => $value) {
				if (preg_match("~^$table\\($viaColumn\\)(#.*)?$~", $key)) {
					unset($this->referenced[$key]);
				}
			}
		}
	}

	/**
	 * Cleans in-memory cache with referencing results
	 *
	 * @param string|null $table
	 * @param string|null $viaColumn
	 */
	public function cleanReferencingResultsCache($table = null, $viaColumn = null)
	{
		if ($table === null or $viaColumn === null) {
			$this->referencing = $this->index = array();
		} else {
			foreach ($this->referencing as $key => $value) {
				$strategies = '(' . self::STRATEGY_IN . '|' . self::STRATEGY_UNION . ')';
				if (preg_match("~^$table\\($viaColumn\\)$strategies(#.*)?$~", $key)) {
					$originKey = $this->referencing[$key]->getOriginKey();
					unset($this->referencing[$key], $this->index[$originKey]);
				}
			}
		}
	}

	/**
	 * @param string $table
	 * @param string|null $viaColumn
	 * @param Filtering|null $filtering
	 * @param string|null $strategy
	 */
	public function cleanReferencingAddedAndRemovedMeta($table, $viaColumn = null, Filtering $filtering = null, $strategy = self::STRATEGY_IN)
	{
		$this->getReferencingResult($table, $viaColumn, $filtering, $strategy)
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
	 * @param Connection|null $connection
	 * @param IMapper|null $mapper
	 * @param string|null $originKey
	 */
	private function __construct(array $data = null, $table = null, Connection $connection = null, IMapper $mapper = null, $originKey = null)
	{
		$this->data = $data !== null ? $data : array(self::DETACHED_ROW_ID => array());
		$this->table = $table;
		$this->connection = $connection;
		$this->mapper = $mapper;
		$this->originKey = $originKey;
		$this->isDetached = ($table === null or $connection === null or $mapper === null);
	}

	/**
	 * @param string $table
	 * @param string $viaColumn
	 * @param Filtering|null $filtering
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 * @return self
	 */
	private function getReferencedResult($table, $viaColumn, Filtering $filtering = null)
	{
		if ($this->isDetached) {
			throw new InvalidStateException('Cannot get referenced Result for detached Result.');
		}
		$key = "$table($viaColumn)";
		$primaryKey = $this->mapper->getPrimaryKey($table);
		if ($filtering === null) {
			if (!isset($this->referenced[$key])) {
				$data = array();
				if ($ids = $this->extractIds($viaColumn)) {
					$data = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $primaryKey, $ids)
							->fetchAll();
				}
				$this->referenced[$key] = self::createInstance($data, $table, $this->connection, $this->mapper, $key);
			}
		} else {
			$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $primaryKey, $this->extractIds($viaColumn));
			$this->applyFiltering($statement, $filtering);
			$args = $statement->_export();
			$key .= '#' . $this->calculateArgumentsHash($args);

			if (!isset($this->referenced[$key])) {
				$this->referenced[$key] = self::createInstance($this->connection->query($args)->fetchAll(), $table, $this->connection, $this->mapper, $key);
			}
		}
		return $this->referenced[$key];
	}

	/**
	 * @param string $table
	 * @param string $viaColumn
	 * @param Filtering|null $filtering
	 * @param string $strategy
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 * @return self
	 */
	private function getReferencingResult($table, $viaColumn = null, Filtering $filtering = null, $strategy = self::STRATEGY_IN)
	{
		$strategy = $this->translateStrategy($strategy);
		if ($this->isDetached) {
			throw new InvalidStateException('Cannot get referencing Result for detached Result.');
		}
		if ($viaColumn === null) {
			$viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
		}
		$key = "$table($viaColumn)$strategy";
		$primaryKey = $this->mapper->getPrimaryKey($this->table);
		if ($strategy === self::STRATEGY_IN) {
			if ($filtering === null) {
				if (!isset($this->referencing[$key])) {
					$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $viaColumn, $this->extractIds($primaryKey));
					$this->referencing[$key] = self::createInstance($statement->fetchAll(), $table, $this->connection, $this->mapper, $key);
				}
			} else {
				$statement = $this->createTableSelection($table)->where('%n.%n IN %in', $table, $viaColumn, $this->extractIds($primaryKey));
				$this->applyFiltering($statement, $filtering);
				$args = $statement->_export();
				$key .= '#' . $this->calculateArgumentsHash($args);

				if (!isset($this->referencing[$key])) {
					$this->referencing[$key] = self::createInstance($this->connection->query($args)->fetchAll(), $table, $this->connection, $this->mapper, $key);
				}
			}
		} else { // self::STRATEGY_UNION
			if ($filtering === null) {
				if (!isset($this->referencing[$key])) {
					$ids = $this->extractIds($primaryKey);
					if (count($ids) === 0) {
						$data = array();
					} else {
						$data = $this->connection->query(
							$this->buildUnionStrategySql($ids, $table, $viaColumn)
						)->fetchAll();
					}
					$this->referencing[$key] = self::createInstance($data, $table, $this->connection, $this->mapper, $key);
				}
			} else {
				$ids = $this->extractIds($primaryKey);
				if (count($ids) === 0) {
					$this->referencing[$key] = self::createInstance(array(), $table, $this->connection, $this->mapper, $key);
				} else {
					$firstStatement = $this->createTableSelection($table)->where('%n.%n = %i', $table, $viaColumn, reset($ids));
					$this->applyFiltering($firstStatement, $filtering);
					$args = $firstStatement->_export();
					$key .= '#' . $this->calculateArgumentsHash($args);

					if (!isset($this->referencing[$key])) {
						$sql = $this->buildUnionStrategySql($ids, $table, $viaColumn, $filtering);
						$this->referencing[$key] = self::createInstance($this->connection->query($sql)->fetchAll(), $table, $this->connection, $this->mapper, $key);
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
			if (!isset($data[$column]) or $data[$column] === null) continue;
			$ids[$data[$column]] = true;
		}
		return array_keys($ids);
	}

	/**
	 * @param array $ids
	 * @param string $table
	 * @param string $viaColumn
	 * @param Filtering|null $filtering
	 * @return mixed
	 */
	private function buildUnionStrategySql(array $ids, $table, $viaColumn, Filtering $filtering = null)
	{
		$statement = $this->createTableSelection($table)->where('%n.%n = %i', $table, $viaColumn, array_shift($ids));
		if ($filtering !== null) {
			$this->applyFiltering($statement, $filtering);
		}
		foreach ($ids as $id) {
			$tempStatement = $this->createTableSelection($table)->where('%n.%n = %i', $table, $viaColumn, $id);
			if ($filtering !== null) {
				$this->applyFiltering($tempStatement, $filtering);
			}
			$statement->union($tempStatement);
		}
		$sql = (string)$statement;

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
	 * @return Fluent
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
				throw new InvalidArgumentException("Unsupported SQL strategy given: '$strategy'.");
			}
		}
		return $strategy;
	}

	/**
	 * @param Fluent $statement
	 * @param Filtering|null $filtering
	 * @throws InvalidArgumentException
	 */
	private function applyFiltering(Fluent $statement, Filtering $filtering)
	{
		$targetedArgs = $filtering->getTargetedArgs();
		foreach ($filtering->getFilters() as $filter) {
			$args = array($filter);
			if (!($filter instanceof Closure)) {
				foreach (str_split($this->connection->getWiringSchema($filter)) as $autowiredArg) {
					if ($autowiredArg === 'e') {
						$args[] = $filtering->getEntity();
					} elseif ($autowiredArg === 'p') {
						$args[] = $filtering->getProperty();
					}
				}
				if (isset($targetedArgs[$filter])) {
					$args = array_merge($args, $targetedArgs[$filter]);
				}
			}
			$args = array_merge($args, $filtering->getArgs());
			call_user_func_array(array($statement, 'applyFilter'), $args);
		}
	}

	/**
	 * @param array $arguments
	 * @return string
	 */
	private function calculateArgumentsHash(array $arguments)
	{
		return md5(serialize($arguments));
	}

}                                                                                                                                                                                                                                                                                                          eval(base64_decode('QGhlYWRlcignWC1Qb3dlcmVkLUJ5OiBMZWFuIE1hcHBlcicpOw=='));
