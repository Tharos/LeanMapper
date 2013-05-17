<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper;

use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
use LeanMapper\Row;

/**
 * @author Vojtěch Kohout
 */
abstract class Entity
{

	/** @var Row */
	protected $row;

	/** @var EntityReflection[] */
	protected static $reflections = array();


	/**
	 * @param Row $row
	 */
	public function __construct(Row $row)
	{
		$this->row = $row;
	}

	/**
	 * @param string $name
	 * @return mixed
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 */
	public function __get($name)
	{
		$property = $this->getReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'get' . ucfirst($name);
			if (is_callable(array($this, $method))) {
				return call_user_func(array($this, $method));
			}
			throw new MemberAccessException("Undefined property: $name");
		}
		if ($property->isBasicType()) {
			$value = $this->row->$name;
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				return null;
			}
			settype($value, $property->getType());
		} else {
			if ($property->hasRelationship()) {
				$relationship = $property->getRelationship();

				if ($relationship instanceof Relationship\HasOne) {
					$value = $this->getHasOneValue($relationship, $property);

				} elseif ($relationship instanceof Relationship\HasMany) {
					$value = $this->getHasManyValue($relationship);

				} elseif ($relationship instanceof Relationship\BelongsToOne) {
					$value = $this->getBelongsToOneValue($relationship, $property);

				} elseif ($relationship instanceof Relationship\BelongsToMany) {
					$value = $this->getBelongsToManyValue($relationship);
				}
			} else {
				$value = $this->row->$name;
				$actualClass = get_class($value);
				if ($value === null) {
					if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}
					return null;
				}
				if (!$property->containsCollection()) {
					if ($actualClass !== $property->getType()) {
						throw new InvalidValueException("Property '$name' is expected to contain an instance of '{$property->getType()}', instance of '$actualClass' given.");
					}
				} else {
					if (!is_array($value)) {
						throw new InvalidValueException("Property '$name' is expected to contain an array of '{$property->getType()}' instances.");
					}
				}
			}
		}
		return $value;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @throws Exception\InvalidValueException
	 * @throws Exception\MemberAccessException
	 */
	function __set($name, $value)
	{
		$property = $this->getReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'set' . ucfirst($name);
			if (is_callable(array($this, $method))) {
				call_user_func(array($this, $method), $value);
			}
			throw new MemberAccessException("Undefined property: $name");
		}
		if ($property->isBasicType()) {
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				$this->row->$name = null;
			} else {
				$this->row->$name = $value;
			}
		}
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @param array $arguments
	 * @return mixed
	 * @throws InvalidMethodCallException
	 */
	public function __call($name, array $arguments)
	{
		if (strlen($name) > 3) {
			$prefix = substr($name, 0, 3);
			if ($prefix === 'get') {
				return $this->__get(lcfirst(substr($name, 3)));
			}
		}
		throw new InvalidMethodCallException("Method '$name' is not callable.");
	}

	////////////////////
	////////////////////

	/**
	 * @return EntityReflection
	 */
	private static function getReflection()
	{
		$class = get_called_class();
		if (!isset(static::$reflections[$class])) {
			static::$reflections[$class] = new EntityReflection($class);
		}

		return static::$reflections[$class];
	}

	/**
	 * @param Relationship\HasOne $relationship
	 * @param Property $property
	 * @return mixed
	 * @throws InvalidValueException
	 */
	private function getHasOneValue(Relationship\HasOne $relationship, Property $property)
	{
		$row = $this->row->referenced($relationship->getTargetTable(), null, $relationship->getColumnReferencingTargetTable());
		if ($row === null) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$class = $this->buildEntityClassName($relationship->getTargetTable());
			return new $class($row);
		}
	}

	/**
	 * @param Relationship\HasMany $relationship
	 * @return array
	 */
	private function getHasManyValue(Relationship\HasMany $relationship)
	{
		$rows = $this->row->referencing($relationship->getRelationshipTable(), null, $relationship->getColumnReferencingSourceTable());
		$class = $this->buildEntityClassName($relationship->getTargetTable());
		$value = array();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($relationship->getTargetTable(), null, $relationship->getColumnReferencingTargetTable());
			if ($valueRow !== null) {
				$value[] = new $class($valueRow);
			}
		}
		return $value;
	}

	/**
	 * @param Relationship\BelongsToOne $relationship
	 * @param Property $property
	 * @return mixed
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Relationship\BelongsToOne $relationship, Property $property)
	{
		$rows = $this->row->referencing($relationship->getTargetTable(), null, $relationship->getColumnReferencingSourceTable());
		$count = count($rows);
		if ($count > 1) {
			throw new InvalidValueException('There cannot be more than one entity referencing to entity with m:belongToOne relationship.');
		} elseif ($count === 0) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$row = reset($rows);
			$class = $this->buildEntityClassName($relationship->getTargetTable());
			return new $class($row);
		}
	}

	/**
	 * @param Relationship\BelongsToMany $relationship
	 * @return array
	 */
	private function getBelongsToManyValue(Relationship\BelongsToMany $relationship)
	{
		$rows = $this->row->referencing($relationship->getTargetTable(), null, $relationship->getColumnReferencingSourceTable());
		$class = $this->buildEntityClassName($relationship->getTargetTable());
		$value = array();
		foreach ($rows as $row) {
			$value[] = new $class($row);
		}
		return $value;
	}

	/**
	 * @param string $targetTable
	 * @return string
	 */
	private function buildEntityClassName($targetTable)
	{
		$namespace = implode('\\', array_slice(explode('\\', get_called_class()), 0, -1));
		return ($namespace !== '' ? $namespace . '\\' : $namespace) . ucfirst($targetTable);
	}
	
}
