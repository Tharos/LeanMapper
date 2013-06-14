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

	/** @var string */
	protected $defaultEntityNamespace = 'Model\Entity';

	/** @var DibiConnection */
	protected $connection;

	/** @var string */
	protected $table;

	/** @var string */
	protected $entityClass;

	/** @var string */
	private $docComment;


	/**
	 * @param DibiConnection $connection
	 */
	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Stores modified fields of entity into database or creates new row in database when entity is in detached state
	 *
	 * @param Entity $entity
	 * @return int
	 */
	public function persist(Entity $entity)
	{
		$this->checkEntityType($entity);
		if ($entity->isModified()) {
			$values = $entity->getModifiedRowData();
			if ($entity->isDetached()) {
				$values = $this->beforeCreate($values);
				$this->connection->insert($this->getTable(), $values)
						->execute(); // dibi::IDENTIFIER would lead to exception when there is no column with AUTO_INCREMENT
				$id = isset($values['id']) ? $values['id'] : $this->connection->getInsertId();
				$entity->markAsCreated($id, $this->getTable(), $this->connection);

				return $id;
			} else {
				$values = $this->beforeUpdate($values);
				$result = $this->connection->update($this->getTable(), $values)
						->where('[id] = %i', $entity->id)
						->execute();
				$entity->markAsUpdated();

				return $result;
			}
		}
	}

	/**
	 * Removes given entity (or entity with given id) from database
	 *
	 * @param Entity|int $arg
	 * @throws InvalidStateException
	 */
	public function delete($arg)
	{
		$id = $arg;
		if ($arg instanceof Entity) {
			$this->checkEntityType($arg);
			if ($arg->isDetached()) {
				throw new InvalidStateException('Cannot delete detached entity.');
			}
			$id = $arg->id;
			$arg->detach();
		}
		$this->connection->delete($this->getTable())
				->where('[id] = %i', $id)
				->execute();
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
		$result = Result::getInstance($row, $table, $this->connection);
		return new $entityClass($result->getRow($row->id));
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
		$collection = Result::getInstance($rows, $table, $this->connection);
		foreach ($rows as $row) {
			$entities[$row->id] = new $entityClass($collection->getRow($row->id));
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
			if ($name !== null) {
				$this->table = $name;
			} else {
				$matches = array();
				if (preg_match('#([a-z0-9]+)repository$#i', get_called_class(), $matches)) {
					$this->table = strtolower($matches[1]);
				} else {
					throw new InvalidStateException('Cannot determine table name.');
				}
			}
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
				$matches = array();
				if (preg_match('#([a-z0-9]+)repository$#i', get_called_class(), $matches)) {
					$this->entityClass = $matches[1];
				} else {
					throw new InvalidStateException('Cannot determine entity class name.');
				}
			}
		}
		return $this->getFullyQualifiedClass($this->entityClass);
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

	/**
	 * @param string $entityClass
	 * @return string
	 */
	private function getFullyQualifiedClass($entityClass)
	{
		if (substr($entityClass, 0, 1) === '\\') {
			return substr($entityClass, 1);
		}
		if ($this->defaultEntityNamespace === null) {
			return $entityClass;
		}
		return $this->defaultEntityNamespace . '\\' . $entityClass;
	}
	
}
