<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

declare(strict_types=1);

namespace LeanMapper;

use ArrayAccess;
use Closure;
use Dibi\Drivers\Sqlite3Driver as DibiSqlite3Driver;
use Dibi\Row as DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;

/**
 * Set of related data, heart of Lean Mapper
 *
 * @author Vojtěch Kohout
 * @implements \Iterator<string, mixed>
 */
class Result implements \Iterator
{

    const STRATEGY_IN = 'in';

    const STRATEGY_UNION = 'union';

    const DETACHED_ROW_ID = -1;

    const KEY_PRELOADED = 'preloaded';

    const ERROR_MISSING_COLUMN = 1;

    /** @var Connection */
    private static $storedConnection;

    /** @var bool */
    private $isDetached;

    /** @var array<int|string, array<string, mixed>> */
    private $data;

    /** @var array<int|string, array<string, bool>> */
    private $modified = [];

    /** @var array<array<string, mixed>> */
    private $added = [];

    /** @var array<array<string, mixed>> */
    private $removed = [];

    /** @var string */
    private $table;

    /** @var Connection */
    private $connection;

    /** @var IMapper */
    protected $mapper;

    /** @var array<int|string> */
    private $keys;

    /** @var array<string, self> */
    private $referenced = [];

    /** @var array<string, array<FilteringResultDecorator>> */
    private $referencedForced = [];

    /** @var array<string, self> */
    private $referencing = [];

    /** @var array<string, array<FilteringResultDecorator>> */
    private $referencingForced = [];

    /** @var array<string, array<int|string, Row[]>> */
    private $index = [];

    /** @var ResultProxy */
    private $proxy;


    /**
     * Creates new common instance (it means persisted)
     *
     * @param \Dibi\Row<string, mixed>|array<\Dibi\Row|array<string, mixed>|ArrayAccess<string, mixed>> $data
     * @throws InvalidArgumentException
     */
    public static function createInstance($data, string $table, Connection $connection, IMapper $mapper): self
    {
        $dataArray = [];
        $primaryKey = $mapper->getPrimaryKey($table);
        if ($data instanceof DibiRow) {
            $dataArray = [isset($data->$primaryKey) ? $data->$primaryKey : self::DETACHED_ROW_ID => $data->toArray()];
        } else {
            $e = new InvalidArgumentException(
                'Invalid type of data given, only ' . DibiRow::class . ', ' . DibiRow::class . '[], ' . ArrayAccess::class . '[] or array of arrays is supported at this moment.'
            );
            if (!is_array($data)) {
                throw $e;
            }
            if (count($data)) {
                /** @var mixed $record */
                $record = reset($data);
                if (!($record instanceof DibiRow) and !is_array($record) and !($record instanceof ArrayAccess)) {
                    throw $e;
                }
            }
            foreach ($data as $record) {
                $record = (array)$record;
                if (isset($record[$primaryKey])) {
                    $dataArray[$record[$primaryKey]] = $record;
                } else {
                    $dataArray[] = $record;
                }
            }
        }
        return new static($dataArray, $table, $connection, $mapper);
    }


    /**
     * Creates new detached instance (it means non-persisted)
     */
    public static function createDetachedInstance(): self
    {
        return new static;
    }


    public static function enableSerialization(Connection $connection): void
    {
        if (self::$storedConnection === null) {
            self::$storedConnection = $connection;
        } elseif (self::$storedConnection !== $connection) {
            throw new InvalidStateException("Given connection doesn't equal to connection already present in Result.");
        }
    }


    /**
     * @throws InvalidStateException
     */
    public function setConnection(Connection $connection): void
    {
        if ($this->connection === null) {
            $this->connection = $connection;
        } elseif ($this->connection !== $connection) {
            throw new InvalidStateException("Given connection doesn't equal to connection already present in Result.");
        }
    }


    public function hasConnection(): bool
    {
        return $this->connection !== null;
    }


    public function setMapper(IMapper $mapper): void
    {
        $this->mapper = $mapper;
    }


    public function getMapper(): ?IMapper
    {
        return $this->mapper;
    }


    /**
     * Creates new Row instance pointing to specific row within Result
     *
     * @param  int|string|null $id
     * @throws InvalidArgumentException
     */
    public function getRow($id = null): ?Row
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
     * @param int|string $id
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getDataEntry($id, string $key)
    {
        if (!isset($this->data[$id])) {
            throw new InvalidArgumentException("Missing row with id $id.");
        }
        if ($this->isAlias($key)) {
            $key = $this->trimAlias($key);
        }
        if (!array_key_exists($key, $this->data[$id])) {
            throw new InvalidArgumentException("Missing '$key' column in row with id $id.", self::ERROR_MISSING_COLUMN);
        }
        return $this->data[$id][$key];
    }


    /**
     * Sets value of given column in row with given id
     *
     * @param int|string $id
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function setDataEntry($id, string $key, $value): void
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
     */
    public function hasDataEntry($id, string $column): bool
    {
        return isset($this->data[$id]) and array_key_exists($column, $this->data[$id]);
    }


    /**
     * Unsets given column in row with given id
     *
     * @param mixed $id
     * @throws InvalidArgumentException
     * @throws InvalidStateException
     */
    public function unsetDataEntry($id, string $column): void
    {
        if (!isset($this->data[$id])) {
            throw new InvalidArgumentException("Missing row with ID $id.");
        }
        unset($this->data[$id][$column], $this->modified[$id][$column]);
    }


    /**
     * Adds new data entry
     *
     * @param  array<string, mixed> $values
     * @throws InvalidStateException
     */
    public function addDataEntry(array $values): void
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
     * @param  array<string, mixed> $values
     * @throws InvalidStateException
     */
    public function removeDataEntry(array $values): void
    {
        if ($this->isDetached) {
            throw new InvalidStateException('Cannot remove data entry to detached Result.');
        }
        foreach ($this->data as $key => $entry) {
            if (array_diff_assoc($values, $entry) === []) {
                $this->removed[] = $entry;
                unset($this->data[$key], $this->modified[$key]);
                break;
            }
        }
    }


    /**
     * Returns values of columns of requested row
     *
     * @param  int|string $id
     * @return array<string, mixed>
     */
    public function getData($id): array
    {
        return isset($this->data[$id]) ? $this->data[$id] : [];
    }


    /**
     * Returns values of columns of requested row that were modified
     *
     * @param  int|string $id
     * @return array<string, mixed>
     */
    public function getModifiedData($id): array
    {
        $result = [];
        if (isset($this->modified[$id])) {
            foreach (array_keys($this->modified[$id]) as $column) {
                $result[$column] = $this->data[$id][$column];
            }
        }
        return $result;
    }


    /**
     * Creates new DataDifference instance relevant to current Result state
     */
    public function createDataDifference(): DataDifference
    {
        return new DataDifference($this->added, $this->removed);
    }


    /**
     * Tells whether requested row is in modified state
     *
     * @param  int|string $id
     */
    public function isModified($id): bool
    {
        return isset($this->modified[$id]) and !empty($this->modified[$id]);
    }


    /**
     * Tells whether Result is in detached state (in means non-persisted)
     */
    public function isDetached(): bool
    {
        return $this->isDetached;
    }


    /**
     * Marks requested row as non-modified (isModified returns false right after this method call)
     *
     * @param  int|string $id
     * @throws InvalidMethodCallException
     */
    public function markAsUpdated($id): void
    {
        if ($this->isDetached) {
            throw new InvalidMethodCallException('Detached Result cannot be marked as updated.');
        }
        unset($this->modified[$id]);
    }


    /**
     * @param int|string $id
     * @throws InvalidStateException
     */
    public function attach($id, string $table): void
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
        $this->data = [
            $id => [$this->mapper->getPrimaryKey($table) => $id] + $modifiedData,
        ];
        $this->modified = [];
        $this->table = $table;
        $this->isDetached = false;
    }


    public function cleanAddedAndRemovedMeta(): void
    {
        $this->added = [];
        $this->removed = [];
    }


    /**
     * Creates new Row instance pointing to requested row in referenced Result
     *
     * @param  int|string $id
     * @throws InvalidStateException
     */
    public function getReferencedRow($id, string $table, ?string $viaColumn = null, ?Filtering $filtering = null): ?Row
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
     * @param  int|string $id
     * @throws InvalidStateException
     * @return Row[]
     */
    public function getReferencingRows($id, string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = null): array
    {
        if ($viaColumn === null) {
            $viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
        }
        $referencingResult = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);
        $resultHash = spl_object_hash($referencingResult);
        if (!isset($this->index[$resultHash])) {
            $column = $this->isAlias($viaColumn) ? $this->trimAlias($viaColumn) : $viaColumn;
            $this->index[$resultHash] = [];
            foreach ($referencingResult as $key => $row) {
                $this->index[$resultHash][$row[$column]][] = new Row($referencingResult, $key);
            }
        }
        if (!isset($this->index[$resultHash][$id])) {
            return [];
        }
        return $this->index[$resultHash][$id];
    }


    public function setReferencedResult(self $referencedResult, string $table, ?string $viaColumn = null): void
    {
        if ($viaColumn === null) {
            $viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
        }
        $this->referenced["$table($viaColumn)#" . self::KEY_PRELOADED] = $referencedResult;
    }


    public function setReferencingResult(self $referencingResult, string $table, ?string $viaColumn = null, ?string $strategy = self::STRATEGY_IN): void
    {
        $strategy = $this->translateStrategy($strategy);
        if ($viaColumn === null) {
            $viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
        }
        $this->referencing["$table($viaColumn)$strategy#" . self::KEY_PRELOADED] = $referencingResult;
        unset($this->index[spl_object_hash($referencingResult)]);
    }


    /**
     * Adds new data entry to referencing Result
     *
     * @param  array<string, mixed> $values
     */
    public function addToReferencing(array $values, string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = self::STRATEGY_IN): void
    {
        $result = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);

        foreach ($result as $key => $entry) {
            if (array_diff_assoc($values, $entry) === []) {
                return;
            }
        }

        $result->addDataEntry($values);
        unset($this->index[spl_object_hash($result)]);
    }


    /**
     * Remove given data entry from referencing Result
     *
     * @param  array<string, mixed> $values
     */
    public function removeFromReferencing(array $values, string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = self::STRATEGY_IN): void
    {
        $result = $this->getReferencingResult($table, $viaColumn, $filtering, $strategy);
        $result->removeDataEntry($values);
        unset($this->index[spl_object_hash($result)]);
    }


    public function createReferencingDataDifference(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = self::STRATEGY_IN): DataDifference
    {
        return $this->getReferencingResult($table, $viaColumn, $filtering, $strategy)
            ->createDataDifference();
    }


    /**
     * Cleans in-memory cache with referenced results
     */
    public function cleanReferencedResultsCache(?string $table = null, ?string $viaColumn = null): void
    {
        if ($table === null or $viaColumn === null) {
            $this->referenced = [];
        } else {
            foreach ($this->referenced as $key => $value) {
                if (preg_match("~^$table\\($viaColumn\\)(#.*)?$~", $key)) {
                    unset($this->referenced[$key]);
                }
            }

            foreach ($this->referencedForced as $key => $value) {
                if (preg_match("~^$table\\($viaColumn\\)(#.*)?$~", $key)) {
                    unset($this->referencedForced[$key]);
                }
            }
        }
    }


    /**
     * Cleans in-memory cache with referencing results
     */
    public function cleanReferencingResultsCache(?string $table = null, ?string $viaColumn = null): void
    {
        if ($table === null or $viaColumn === null) {
            $this->referencing = $this->index = [];
            $this->referencingForced = [];
        } else {
            $strategies = '(' . self::STRATEGY_IN . '|' . self::STRATEGY_UNION . ')';

            foreach ($this->referencing as $key => $value) {
                if (preg_match("~^$table\\($viaColumn\\)$strategies(#.*)?$~", $key)) {
                    unset($this->index[spl_object_hash($this->referencing[$key])]);
                    unset($this->referencing[$key]);
                }
            }

            foreach ($this->referencingForced as $key => $value) {
                if (preg_match("~^$table\\($viaColumn\\)$strategies(#.*)?$~", $key)) {
                    unset($this->referencingForced[$key]);
                }
            }
        }
    }


    public function cleanReferencingAddedAndRemovedMeta(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = self::STRATEGY_IN): void
    {
        $this->getReferencingResult($table, $viaColumn, $filtering, $strategy)
            ->cleanAddedAndRemovedMeta();
    }


    /**
     * @throws InvalidArgumentException
     */
    public function getProxy(string $proxyClass): ResultProxy
    {
        if ($this->proxy === null) {
            $this->proxy = new $proxyClass($this);
        }
        if (!is_a($this->proxy, $proxyClass)) {
            throw new InvalidArgumentException('Inconsistent proxy class requested.');
        }
        return $this->proxy;
    }


    /**
     * @return array
     */
    public function __sleep()
    {
        if (self::$storedConnection === null and $this->connection !== null) {
            self::enableSerialization($this->connection);
        }

        return ['isDetached', 'data', 'modified', 'added', 'removed', 'table', 'mapper', 'keys', 'referenced', 'referencing', 'index', 'proxy'];
    }


    public function __wakeup()
    {
        if (self::$storedConnection !== null) {
            $this->setConnection(self::$storedConnection);
        }
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


    public function next(): void
    {
        next($this->keys);
    }


    /**
     * @return mixed
     */
    public function key()
    {
        return current($this->keys);
    }


    public function valid(): bool
    {
        return current($this->keys) !== false;
    }


    public function rewind(): void
    {
        $this->keys = array_keys($this->data);
        reset($this->keys);
    }

    ////////////////////
    ////////////////////

    /**
     * @param array<int|string, array<string, mixed>>|null $data
     */
    private function __construct(?array $data = null, ?string $table = null, ?Connection $connection = null, ?IMapper $mapper = null)
    {
        $this->data = $data !== null ? $data : [self::DETACHED_ROW_ID => []];
        $this->table = $table;
        $this->connection = $connection;
        $this->mapper = $mapper;
        $this->isDetached = ($table === null or $connection === null or $mapper === null);
    }


    /**
     * @throws InvalidArgumentException
     * @throws InvalidStateException
     */
    private function getReferencedResult(string $table, string $viaColumn, ?Filtering $filtering = null): self
    {
        if ($this->isDetached) {
            throw new InvalidStateException('Cannot get referenced Result for detached Result.');
        }
        $key = "$table($viaColumn)";
        $primaryKey = null;
        $ids = null;
        if (isset($this->referencedForced[$key])) {
            $ids = $this->extractIds($viaColumn);
            $primaryKey = $this->mapper->getPrimaryKey($table);

            foreach ($this->referencedForced[$key] as $filteringResult) {
                if ($filteringResult->isValidFor($ids, $filtering->getArgs())) {
                    return $filteringResult->getResult();
                }
            }
        }
        if (isset($this->referenced[$preloadedKey = $key . '#' . self::KEY_PRELOADED])) {
            return $this->referenced[$preloadedKey];
        }
        if ($filtering === null) {
            if (!isset($this->referenced[$key])) {
                if (!isset($ids)) {
                    $ids = $this->extractIds($viaColumn);
                    $primaryKey = $this->mapper->getPrimaryKey($table);
                }
                $data = [];
                if (!empty($ids)) {
                    $data = $this->createTableSelection($table, $ids)
                        ->where('%n.%n IN %in', $table, $primaryKey, $ids)
                        ->execute()->setRowClass(null)->fetchAll();
                }
                $this->referenced[$key] = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
            }
            return $this->referenced[$key];
        }

        // $filtering !== null
        if (!isset($ids)) {
            $ids = $this->extractIds($viaColumn);
            $primaryKey = $this->mapper->getPrimaryKey($table);
        }
        $statement = $this->createTableSelection($table, $ids)->where('%n.%n IN %in', $table, $primaryKey, $ids);
        $filteringResult = $this->applyFiltering($statement, $filtering);

        if ($filteringResult instanceof FilteringResultDecorator) {
            if (!isset($this->referencedForced[$key])) {
                $this->referencedForced[$key] = [];
            }
            $this->referencedForced[$key][] = $filteringResult;
            return $filteringResult->getResult();
        }

        $args = $statement->_export();
        $key .= '#' . $this->calculateArgumentsHash($args);

        if (!isset($this->referenced[$key])) {
            $data = $this->connection->query($args)->setRowClass(null)->fetchAll();
            $this->referenced[$key] = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
        }
        return $this->referenced[$key];
    }


    /**
     * @throws InvalidArgumentException
     * @throws InvalidStateException
     */
    private function getReferencingResult(string $table, ?string $viaColumn = null, ?Filtering $filtering = null, ?string $strategy = self::STRATEGY_IN): self
    {
        $strategy = $this->translateStrategy($strategy);
        if ($this->isDetached) {
            throw new InvalidStateException('Cannot get referencing Result for detached Result.');
        }
        if ($viaColumn === null) {
            $viaColumn = $this->mapper->getRelationshipColumn($table, $this->table);
        }
        $key = "$table($viaColumn)$strategy";
        $ids = null;
        if (isset($this->referencingForced[$key])) {
            $ids = $this->extractIds($this->mapper->getPrimaryKey($this->table));
            foreach ($this->referencingForced[$key] as $filteringResult) {
                if ($filteringResult->isValidFor($ids, $filtering->getArgs())) {
                    return $filteringResult->getResult();
                }
            }
        }
        if (isset($this->referencing[$preloadedKey = $key . '#' . self::KEY_PRELOADED])) {
            return $this->referencing[$preloadedKey];
        }
        if ($strategy === self::STRATEGY_IN) {
            if ($filtering === null) {
                if (!isset($this->referencing[$key])) {
                    isset($ids) or $ids = $this->extractIds($this->mapper->getPrimaryKey($this->table));
                    $statement = $this->createTableSelection($table, $ids);
                    if ($this->isAlias($viaColumn)) {
                        $statement->where('%n IN %in', $this->trimAlias($viaColumn), $ids);
                    } else {
                        $statement->where('%n.%n IN %in', $table, $viaColumn, $ids);
                    }
                    $data = $statement->execute()->setRowClass(null)->fetchAll();
                    $this->referencing[$key] = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
                }
            } else {
                isset($ids) or $ids = $this->extractIds($this->mapper->getPrimaryKey($this->table));
                $statement = $this->createTableSelection($table, $ids);
                if ($this->isAlias($viaColumn)) {
                    $statement->where('%n IN %in', $this->trimAlias($viaColumn), $ids);
                } else {
                    $statement->where('%n.%n IN %in', $table, $viaColumn, $ids);
                }
                $filteringResult = $this->applyFiltering($statement, $filtering);

                if ($filteringResult instanceof FilteringResultDecorator) {
                    if (!isset($this->referencingForced[$key])) {
                        $this->referencingForced[$key] = [];
                    }
                    $this->referencingForced[$key][] = $filteringResult;
                    return $filteringResult->getResult();
                }
                $args = $statement->_export();
                $key .= '#' . $this->calculateArgumentsHash($args);

                if (!isset($this->referencing[$key])) {
                    $data = $this->connection->query($args)->setRowClass(null)->fetchAll();
                    $this->referencing[$key] = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
                }
            }
            return $this->referencing[$key];
        }

        // $strategy === self::STRATEGY_UNION
        if ($filtering === null) {
            if (!isset($this->referencing[$key])) {
                isset($ids) or $ids = $this->extractIds($this->mapper->getPrimaryKey($this->table));
                if (count($ids) === 0) {
                    $data = [];
                } else {
                    $data = $this->connection->query(
                        $this->buildUnionStrategySql($ids, $table, $viaColumn)
                    )->setRowClass(null)->fetchAll();
                }
                $this->referencing[$key] = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
            }
        } else {
            isset($ids) or $ids = $this->extractIds($this->mapper->getPrimaryKey($this->table));
            if (count($ids) === 0) {
                $this->referencing[$key] = self::createInstance([], $table, $this->connection, $this->mapper);
            } else {
                $firstStatement = $this->createTableSelection($table, [reset($ids)]);
                if ($this->isAlias($viaColumn)) {
                    $firstStatement->where('%n = ?', $this->trimAlias($viaColumn), reset($ids));
                } else {
                    $firstStatement->where('%n.%n = ?', $table, $viaColumn, reset($ids));
                }
                $filteringResult = $this->applyFiltering($firstStatement, $filtering);

                if ($filteringResult instanceof FilteringResultDecorator) {
                    if (!isset($this->referencingForced[$key])) {
                        $this->referencingForced[$key] = [];
                    }
                    $this->referencingForced[$key][] = $filteringResult;
                    return $filteringResult->getResult();
                }
                $args = $firstStatement->_export();
                $key .= '#' . $this->calculateArgumentsHash($args);

                if (!isset($this->referencing[$key])) {
                    $sql = $this->buildUnionStrategySql($ids, $table, $viaColumn, $filtering);
                    $data = $this->connection->query($sql)->setRowClass(null)->fetchAll();
                    $result = self::createInstance(Helpers::convertDbRows($table, $data, $this->mapper), $table, $this->connection, $this->mapper);
                    $this->referencing[$key] = $result;
                }
            }
        }
        return $this->referencing[$key];
    }


    /**
     * @return array<int|string>
     */
    private function extractIds(string $column): array
    {
        if ($this->isAlias($column)) {
            $column = $this->trimAlias($column);
        }
        $ids = [];
        foreach ($this->data as $data) {
            if (!isset($data[$column])) {
                continue;
            }
            $ids[$data[$column]] = true;
        }
        return array_keys($ids);
    }


    /**
     * @param  array<int|string> $ids
     */
    private function buildUnionStrategySql(array $ids, string $table, string $viaColumn, ?Filtering $filtering = null): string
    {
        $isAlias = $this->isAlias($viaColumn);
        if ($isAlias) {
            $viaColumn = $this->trimAlias($viaColumn);
        }
        $statements = [];
        foreach ($ids as $id) {
            $statement = $this->createTableSelection($table, [$id]);
            if ($isAlias) {
                $statement->where('%n = ?', $viaColumn, $id);
            } else {
                $statement->where('%n.%n = ?', $table, $viaColumn, $id);
            }
            if ($filtering !== null) {
                $this->applyFiltering($statement, $filtering);
            }
            $statements[] = ltrim((string) $statement);
        }
        $driver = $this->connection->getDriver();
        if ($driver instanceof DibiSqlite3Driver) {
            return 'SELECT * FROM (' . implode(') UNION SELECT * FROM (', $statements) . ')';
        }
        return '(' . implode(') UNION (', $statements) . ')';
    }


    /**
     * @param  array<int|string>|null $relatedKeys
     */
    private function createTableSelection(string $table, ?array $relatedKeys = null): Fluent
    {
        $selection = $this->connection->select('%n.*', $table)->from('%n', $table);
        return $relatedKeys !== null ? $selection->setRelatedKeys($relatedKeys) : $selection;
    }


    /**
     * @throws InvalidArgumentException
     */
    private function translateStrategy(?string $strategy): string
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
     * @throws InvalidArgumentException
     */
    private function applyFiltering(Fluent $statement, Filtering $filtering): ?FilteringResultDecorator
    {
        $targetedArgs = $filtering->getTargetedArgs();
        foreach ($filtering->getFilters() as $filter) {
            $baseArgs = [];
            if (!($filter instanceof Closure)) {
                foreach (str_split($this->connection->getWiringSchema($filter)) as $autowiredArg) {
                    if ($autowiredArg === 'e') {
                        $baseArgs[] = $filtering->getEntity();
                    } elseif ($autowiredArg === 'p') {
                        $baseArgs[] = $filtering->getProperty();
                    }
                }
                if (isset($targetedArgs[$filter])) {
                    $baseArgs = array_merge($baseArgs, $targetedArgs[$filter]);
                }
            }
            $result = call_user_func_array([$statement, 'applyFilter'], array_merge([$filter], $baseArgs, $filtering->getArgs()));
            if ($result instanceof FilteringResult) {
                return new FilteringResultDecorator($result, $baseArgs);
            }
        }
        return null;
    }


    /**
     * @param  array<mixed> $arguments
     */
    private function calculateArgumentsHash(array $arguments): string
    {
        return md5(serialize($arguments));
    }


    private function isAlias(string $column): bool
    {
        return strncmp($column, '*', 1) === 0;
    }


    private function trimAlias(string $column): string
    {
        return substr($column, 1);
    }

}
