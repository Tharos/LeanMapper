<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper;

use dibi;
use DibiConnection;
use DibiRow;
use LeanMapper\Exception\InvalidStateException;
use Nette\Reflection\ClassType;

/**
 * @author Vojtěch Kohout
 */
abstract class Repository
{

	const DEFAULT_ENTITY_NAMESPACE = 'Model\Entity';

	/** @var DibiConnection */
	protected $connection;

	/** @var string */
	protected $table;

	/** @var string */
	protected $entityClass;

	/** @var ClassType */
	private $reflection;


	/**
	 * @param DibiConnection $connection
	 */
	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param Entity $entity
	 * @return int
	 */
	public function persist(Entity $entity)
	{
		if ($entity->isModified()) {
			$values = $entity->getModifiedData();
			if ($entity->isDetached()) {
				$this->connection->insert($this->getTable(), $values)
						->execute(); // dibi::IDENTIFIER would lead to exception when there is no column with AUTO_INCREMENT
				$id = isset($values['id']) ? $values['id'] : $this->connection->getInsertId();
				$entity->markAsCreated($id, $this->getTable(), $this->connection);
				return $id;
			} else {
				$result = $this->connection->update($this->getTable(), $values)
						->where('[id] = %i', $entity->id)
						->execute();
				$entity->markAsUpdated();
				return $result;
			}
		}
	}

	/**
	 * @param Entity|int $arg
	 */
	public function delete($arg)
	{
		$id = ($arg instanceof Entity) ? $arg->id : $arg;
		$this->connection->delete($this->getTable())
				->where('[id] = %i', $id)
				->execute();
	}

	/**
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
		$collection = Result::getInstance($row, $table, $this->connection);
		return new $entityClass($collection->getRow($row->id));
	}

	/**
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
		return $entities;
	}

	/**
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getTable()
	{
		if ($this->table === null) {
			$reflection = $this->getReflection();
			if (($name = $reflection->getAnnotation('table')) !== null) {
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
	 * @return string
	 * @throws InvalidStateException
	 */
	protected function getEntityClass()
	{
		if ($this->entityClass === null) {
			$reflection = $this->getReflection();
			if (($name = $reflection->getAnnotation('entity')) !== null) {
				$this->entityClass = $name;
			} else {
				$matches = array();
				if (preg_match('#([a-z0-9]+)repository$#i', get_called_class(), $matches)) {
					$this->entityClass = self::DEFAULT_ENTITY_NAMESPACE . '\\' . $matches[1];
				} else {
					throw new InvalidStateException('Cannot determine entity class name.');
				}
			}
		}
		return $this->entityClass;
	}

	////////////////////
	////////////////////

	/**
	 * @return ClassType
	 */
	private function getReflection()
	{
		if ($this->reflection === null) {
			$this->reflection = new ClassType(get_called_class());
		}
		return $this->reflection;
	}
	
}
