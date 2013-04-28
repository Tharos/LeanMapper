<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper;

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
		$collection = new Result($row, $table, $this->connection);
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
		$collection = new Result($rows, $table, $this->connection);
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
