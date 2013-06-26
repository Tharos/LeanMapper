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

use dibi;
use DibiConnection;
use DibiRow;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Reflection\AnnotationsParser;
use ReflectionClass;

/**
 * Base class for custom repositories
 *
 * @author Vojtěch Kohout
 */
abstract class Repository
{

	/** @var DibiConnection */
	protected $connection;

	/** @var IMapper */
	protected $mapper;

	/** @var string */
	protected $table;

	/** @var string */
	protected $entityClass;

	/** @var string */
	private $docComment;


	/**
	 * @param DibiConnection $connection
	 * @param IMapper $mapper
	 */
	public function __construct(DibiConnection $connection, IMapper $mapper)
	{
		$this->connection = $connection;
		$this->mapper = $mapper;
	}

	/**
	 * Stores modified fields of entity into database or creates new row in database when entity is in detached state
	 *
	 * @param Entity $entity
	 * @return mixed
	 */
	public function persist(Entity $entity)
	{
		$result = null;
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		$this->checkEntityType($entity);
		if ($entity->isModified()) {
			if ($entity->isDetached()) {
				$entity->useMapper($this->mapper);

				$values = $this->beforeCreate($entity->getModifiedRowData());
				$this->connection->query(
					'INSERT INTO %n %v', $this->getTable(), $values
				);
				$id = isset($values[$primaryKey]) ? $values[$primaryKey] : $this->connection->getInsertId();
				$entity->markAsCreated($id, $this->getTable(), $this->connection);

				return $id;
			} else {
				$values = $this->beforeUpdate($entity->getModifiedRowData());
				$result = $this->connection->query(
					'UPDATE %n SET %a WHERE %n = ?', $this->getTable(), $values, $primaryKey, $entity->$idField
				);
				$entity->markAsUpdated();
			}
		}
		$this->persistHasManyChanges($entity);
		return $result;
	}

	/**
	 * Removes given entity (or entity with given id) from database
	 *
	 * @param Entity|int $arg
	 * @throws InvalidStateException
	 */
	public function delete($arg)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		$id = $arg;
		if ($arg instanceof Entity) {
			$this->checkEntityType($arg);
			if ($arg->isDetached()) {
				throw new InvalidStateException('Cannot delete detached entity.');
			}
			$id = $arg->$idField;
			$arg->detach();
		}
		$this->connection->query(
			'DELETE FROM %n WHERE %n = ?', $this->getTable(), $primaryKey, $id
		);
	}

	/**
	 * @param Entity $entity
	 */
	protected function persistHasManyChanges(Entity $entity)
	{
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		$idField = $this->mapper->getEntityField($this->getTable(), $primaryKey);

		$multiInsert = array();
		foreach ($entity->getHasManyRowDifferences() as $key => $difference) {
			list($columnReferencingSourceTable, $relationshipTable, $columnReferencingTargetTable) = explode(':', $key);
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
						'DELETE FROM %n WHERE %n = ? %lmt', $relationshipTable, $columnReferencingTargetTable, $value, - $count
					);
				}
			}
		}
		if (!empty($multiInsert)) {
			$this->connection->query(
				'INSERT INTO %n %ex', $relationshipTable, $multiInsert
			);
		}
	}

	/**
	 * Adjusts prepared values before database insert call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforeCreate(array $values)
	{
		return $this->beforePersist($values);
	}

	/**
	 * Adjusts prepared values before database update call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforeUpdate(array $values)
	{
		return $this->beforePersist($values);
	}

	/**
	 * Adjusts prepared values before database insert or update call
	 *
	 * @param array $values
	 * @return array
	 */
	protected function beforePersist(array $values)
	{
		return $values;
	}

	/**
	 * Helps to create entity instance from given DibiRow instance
	 *
	 * @param DibiRow $row
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return mixed
	 */
	protected function createEntity(DibiRow $row, $entityClass = null, $table = null)
	{
		if ($entityClass === null) {
			$entityClass = $this->getEntityClass();
		}
		if ($table === null) {
			$table = $this->getTable();
		}
		$result = Result::getInstance($row, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		return new $entityClass($result->getRow($row->$primaryKey));
	}

	/**
	 * Helps to create array of entities from given array of DibiRow instances
	 *
	 * @param array $rows
	 * @param string|null $entityClass
	 * @param string|null $table
	 * @return array
	 */
	protected function createEntities(array $rows, $entityClass = null, $table = null)
	{
		if ($entityClass === null) {
			$entityClass = $this->getEntityClass();
		}
		if ($table === null) {
			$table = $this->getTable();
		}
		$entities = array();
		$collection = Result::getInstance($rows, $table, $this->connection, $this->mapper);
		$primaryKey = $this->mapper->getPrimaryKey($this->getTable());
		foreach ($rows as $row) {
			$entities[$row->$primaryKey] = new $entityClass($collection->getRow($row->$primaryKey));
		}
		return $this->createCollection($entities);
	}

	/**
	 * Returns name of database table related to entity which repository can handle
	 *
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getTable()
	{
		if ($this->table === null) {
			$name = AnnotationsParser::parseSimpleAnnotationValue('table', $this->getDocComment());
			$this->table = $name !== null ? $name : $this->mapper->getTableByRepositoryClass(get_called_class());
		}
		return $this->table;
	}

	/**
	 * Returns fully qualified name of entity class which repository can handle
	 *
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getEntityClass()
	{
		if ($this->entityClass === null) {
			$entityClass = AnnotationsParser::parseSimpleAnnotationValue('entity', $this->getDocComment());
			if ($entityClass !== null) {
				$this->entityClass = $entityClass;
			} else {
				$this->entityClass = $this->mapper->getEntityClass($this->mapper->getTableByRepositoryClass(get_called_class()));
			}
		}
		return $this->entityClass;
	}

	/**
	 * @param Entity $entity
	 * @throws InvalidArgumentException
	 */
	protected function checkEntityType(Entity $entity)
	{
		$entityClass = $this->getEntityClass();
		if (!($entity instanceof $entityClass)) {
			throw new InvalidArgumentException('Repository ' . get_called_class() . ' cannot handle ' . get_class($entity) . ' entity.');
		}
	}

	/**
	 * @param array $entities
	 * @return array
	 */
	protected function createCollection(array $entities)
	{
		return $entities;
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
