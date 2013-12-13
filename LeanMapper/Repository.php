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

use DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Reflection\AnnotationsParser;
use ReflectionClass;

/**
 * Base class for concrete repositories
 *
 * @author Vojtěch Kohout
 */
abstract class Repository
{

	/** @var Connection */
	protected $connection;

	/** @var IMapper */
	protected $mapper;

	/** @var IEntityFactory */
	protected $entityFactory;

	/** @var string */
	protected $table;

	/** @var string */
	protected $entityClass;

	/** @var Events */
	protected $events;

	/** @var string */
	private $docComment;

	/** @var bool */
	private $tableAnnotationChecked = false;


	/**
	 * @param Connection $connection
	 * @param IMapper $mapper
	 * @param IEntityFactory $entityFactory
	 */
	public function __construct(Connection $connection, IMapper $mapper, IEntityFactory $entityFactory)
	{
		$this->connection = $connection;
		$this->mapper = $mapper;
		$this->entityFactory = $entityFactory;
		$this->events = new Events;
		$this->initEvents();
	}

	/**
	 * @param string $name
	 * @return array|null
	 */
	public function &__get($name)
	{
		if (preg_match('#^on[A-Z]#', $name)) {
			return $this->events->getCallbacksReference(lcfirst(substr($name, 2)));
		}
	}

	/**
	 * @return Fluent
	 */
	protected function createFluent(/*$filterArg1, $filterArg2, ...*/)
	{
		$table = $this->getTable();
		$statement = $this->connection->select('%n.*', $table)->from($table);
		$filters = $this->mapper->getImplicitFilters($this->mapper->getEntityClass($table), new Caller($this));
		if (!empty($filters)) {
			$targetedArgs = array();
			$funcArgs = func_get_args();
			if ($filters instanceof ImplicitFilters) {
				$targetedArgs = $filters->getTargetedArgs();
				$filters = $filters->getFilters();
			}
			foreach ($filters as $filter) {
				$args = array($filter);
				if (is_string($filter) and array_key_exists($filter, $targetedArgs)) {
					$args = array_merge($args, $targetedArgs[$filter]);
				}
				if (!empty($funcArgs)) {
					$args = array_merge($args, $funcArgs);
				}
				call_user_func_array(array($statement, 'applyFilter'), $args);
			}
		}
		return $statement;
	}

	/**
	 * Allows initialize repository's events
	 */
	protected function initEvents()
	{
	}

	/**
	 * Stores values of entity's modified properties into database (inserts new row when entity is in detached state)
	 *
	 * @param Entity $entity
	 * @return mixed
	 */
	public function persist(Entity $entity)
	{
		$this->checkEntityType($entity);

		$this->events->invokeCallbacks(Events::EVENT_BEFORE_PERSIST, $entity);
		if ($entity->isDetached()) {
			$entity->makeAlive($this->entityFactory, $this->connection, $this->mapper);
			$this->events->invokeCallbacks(Events::EVENT_BEFORE_CREATE, $entity);
			$result = $id = $this->insertIntoDatabase($entity);
			$entity->attach($id);
			$this->events->invokeCallbacks(Events::EVENT_AFTER_CREATE, $entity);
		} elseif ($entity->isModified()) {
			$this->events->invokeCallbacks(Events::EVENT_BEFORE_UPDATE, $entity);
			$result = $this->updateInDatabase($entity);
			$entity->markAsUpdated();
			$this->events->invokeCallbacks(Events::EVENT_AFTER_UPDATE, $entity);
		}
		$this->persistHasManyChanges($entity);
		$this->events->invokeCallbacks(Events::EVENT_AFTER_PERSIST, $entity);

		return isset($result) ? $result : null;
	}

	/**
	 * Removes given entity (or entity with given id) from database
	 *
	 * @param mixed $arg
	 * @throws InvalidStateException
	 */
	public function delete($arg)
	{
		$this->events->invokeCallbacks(Events::EVENT_BEFORE_DELETE, $arg);
		if ($arg instanceof Entity) {
			$this->checkEntityType($arg);
			if ($arg->isDetached()) {
				throw new InvalidStateException('Cannot delete detached entity.');
			}
		}
		$this->deleteFromDatabase($arg);
		if ($arg instanceof Entity) {
			$arg->detach();
		}
		$this->events->invokeCallbacks(Events::EVENT_AFTER_DELETE, $arg);
	}

	/**
	 * Performs database insert (can be customized)
	 *
	 * @param Entity $entity
	 * @return mixed
	 */
	protected function insertIntoDatabase(Entity $entity)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$values = $entity->getModifiedRowData();
		$this->connection->query(
			'INSERT INTO %n %v', $this->getTable(), $values
		);
		return isset($values[$primaryKey]) ? $values[$primaryKey] : $this->connection->getInsertId();
	}

	/**
	 * Performs database update (can be customized)
	 *
	 * @param Entity $entity
	 * @return mixed
	 */
	protected function updateInDatabase(Entity $entity)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);
		$values = $entity->getModifiedRowData();
		return $this->connection->query(
			'UPDATE %n SET %a WHERE %n = ?', $this->getTable(), $values, $primaryKey, $entity->$idField
		);
	}

	/**
	 * Performs database delete (can be customized)
	 *
	 * @param mixed $arg
	 */
	protected function deleteFromDatabase($arg)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		$id = ($arg instanceof Entity) ? $arg->$idField : $arg;
		$this->connection->query(
			'DELETE FROM %n WHERE %n = ?', $this->getTable(), $primaryKey, $id
		);
	}

	/**
	 * Persists changes in M:N relationships
	 *
	 * @param Entity $entity
	 */
	protected function persistHasManyChanges(Entity $entity)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		foreach ($entity->getHasManyRowDifferences() as $key => $difference) {
			list($columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable) = explode(':', $key);
			$multiInsert = array();
			foreach ($difference as $value => $count) {
				if ($count > 0) {
					for ($i = 0; $i < $count; $i++) {
						$multiInsert[] = array(
							$columnReferencingSourceTable => $entity->$idField,
							$columnReferencingTargetTable => $value,
						);
					}
				} else {
					$this->connection->query(
						'DELETE FROM %n WHERE %n = ? AND %n = ? %lmt', $relationshipTable, $columnReferencingSourceTable, $entity->$idField, $columnReferencingTargetTable, $value, -$count
					);
				}
			}
			if (!empty($multiInsert)) {
				$this->connection->query(
					'INSERT INTO %n %ex', $relationshipTable, $multiInsert
				);
			}
		}
	}

	/**
	 * Creates new Entity instance from given DibiRow instance
	 *
	 * @param DibiRow $dibiRow
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return mixed
	 */
	protected function createEntity(DibiRow $dibiRow, $entityClass = null, $table = null)
	{
		if ($table === null) {
			$table = $this->getTable();
		}
		$result = Result::createInstance($dibiRow, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());

		$row = $result->getRow($dibiRow->$primaryKey);
		if ($entityClass === null) {
			$entityClass = $this->mapper->getEntityClass($this->getTable(), $row);
		}
		$entity = $this->entityFactory->createEntity($entityClass, $row);
		$entity->makeAlive($this->entityFactory);
		return $entity;
	}

	/**
	 * Creates new set of Entity's instances from given array of DibiRow instances
	 *
	 * @param DibiRow[] $rows
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return array
	 */
	protected function createEntities(array $rows, $entityClass = null, $table = null)
	{
		if ($table === null) {
			$table = $this->getTable();
		}
		$entities = array();
		$collection = Result::createInstance($rows, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		if ($entityClass !== null) {
			foreach ($rows as $dibiRow) {
				$entity = $this->entityFactory->createEntity(
					$entityClass, $collection->getRow($dibiRow->$primaryKey)
				);
				$entity->makeAlive($this->entityFactory);
				$entities[$dibiRow->$primaryKey] = $entity;
			}
		} else {
			foreach ($rows as $dibiRow) {
				$row = $collection->getRow($dibiRow->$primaryKey);
				$entityClass = $this->mapper->getEntityClass($this->getTable(), $row);
				$entity = $this->entityFactory->createEntity($entityClass, $row);
				$entity->makeAlive($this->entityFactory);
				$entities[$dibiRow->$primaryKey] = $entity;
			}
		}
		return $this->entityFactory->createCollection($entities);
	}

	/**
	 * Gets name of (main) database table related to entity that repository can handle
	 *
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getTable()
	{
		if ($this->table === null) {
			if (!$this->tableAnnotationChecked) {
				$this->tableAnnotationChecked = true;
				$table = AnnotationsParser::parseSimpleAnnotationValue('table', $this->getDocComment());
				if ($table !== null) {
					return $this->table = $table;
				}
			}
			return $this->mapper->getTableByRepositoryClass(get_called_class());
		}
		return $this->table;
	}

	/**
	 * Checks whether give entity is instance of required type
	 *
	 * @param Entity $entity
	 * @throws InvalidArgumentException
	 */
	protected function checkEntityType(Entity $entity)
	{
		$entityClass = $this->mapper->getEntityClass($this->getTable());
		if (!($entity instanceof $entityClass)) {
			throw new InvalidArgumentException('Repository ' . get_called_class() . ' cannot handle ' . get_class($entity) . ' entity.');
		}
	}

	////////////////////
	////////////////////

	/**
	 * @return string
	 */
	private function getDocComment()
	{
		if ($this->docComment === null) {
			$reflection = new ReflectionClass(get_called_class());
			$this->docComment = $reflection->getDocComment();
		}
		return $this->docComment;
	}

}
