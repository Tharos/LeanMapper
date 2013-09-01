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

use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Exception\RuntimeException;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
use LeanMapper\Row;
use Traversable;

/**
 * Base class for concrete entities
 *
 * @author Vojtěch Kohout
 */
abstract class Entity
{

	const ACTION_ADD = 'add';

	const ACTION_REMOVE = 'remove';

	/** @var Row */
	protected $row;

	/** @var IMapper */
	protected $mapper;

	/** @var EntityReflection[] */
	protected static $reflections = array();

	/** @var EntityReflection */
	private $currentReflection;


	/**
	 * Gets reflection of current entity
	 *
	 * @param IMapper|null $mapper
	 * @return EntityReflection
	 */
	public static function getReflection(IMapper $mapper = null)
	{
		$class = get_called_class();
		$mapperClass = $mapper !== null ? get_class($mapper) : '';
		if (!isset(static::$reflections[$class][$mapperClass])) {
			static::$reflections[$class][$mapperClass] = new EntityReflection($class, $mapper);
		}
		return static::$reflections[$class][$mapperClass];
	}

	/**
	 * @param Row|Traversable|array|null $arg
	 * @throws InvalidArgumentException
	 */
	public function __construct($arg = null)
	{
		if ($arg instanceof Row) {
			if ($arg->isDetached()) {
				throw new InvalidArgumentException('It is not allowed to create entity from detached instance of LeanMapper\Row.');
			}
			$this->row = $arg;
			$this->mapper = $arg->getMapper();
		} else {
			$this->row = Result::getDetachedInstance()->getRow();
			foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
				if ($property->hasDefaultValue()) {
					$propertyName = $property->getName();
					$this->$propertyName = $property->getDefaultValue();
				}
			}
			$this->initDefaults();
			if ($arg !== null) {
				if (!is_array($arg) and !($arg instanceof Traversable)) {
					$type = gettype($arg) !== 'object' ? gettype($arg) : 'instance of ' . get_class($arg);
					throw new InvalidArgumentException("Argument \$arg in LeanMapper\\Entity::__construct must contain either null, array, instance of LeanMapper\\Row or instance of Traversable, $type given.");
				}
				$this->assign($arg);
			}
		}
	}

	/**
	 * Gets value of given property
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 * @throws RuntimeException
	 * @throws InvalidMethodCallException
	 */
	public function __get($name /*, array $filterArgs*/)
	{
		$reflection = $this->getCurrentReflection();
		$nativeGetter = $reflection->getGetter('get' . ucfirst($name));
		if ($nativeGetter !== null) {
			return $nativeGetter->invoke($this); // filters are not relevant here
		}
		$property = $reflection->getEntityProperty($name);
		if ($property === null) {
			throw new MemberAccessException("Cannot access undefined property '$name'.");
		}
		$customGetter = $property->getGetter();
		if ($customGetter !== null) {
			$customGetterReflection = $reflection->getGetter($customGetter);
			if ($customGetterReflection === null) {
				throw new InvalidMethodCallException("Missing getter method '$customGetter'.");
			}
			return $customGetterReflection->invoke($this); // filters are not relevant here
		}
		$pass = $property->getGetterPass();
		if ($property->isBasicType()) {
			$column = $property->getColumn();
			$value = $this->row->$column;
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
			} else {
				if (!settype($value, $property->getType())) {
					throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
				}
				if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
					throw new InvalidValueException("Given value is not from possible values enumeration.");
				}
			}
		} else {
			if ($property->hasRelationship()) {
				$relationship = $property->getRelationship();

				$method = explode('\\', get_class($relationship));
				$method = 'get' . end($method) . 'Value';

				$args = array($property);
				$firstFilters = $property->getFilters(0);
				if ($method === 'getHasManyValue') {
					$secondFilters = $property->getFilters(1);
				}
				if (!empty($firstFilters) or !empty($secondFilters)) {
					$funcArgs = func_get_args();
					$filterArgs = isset($funcArgs[1]) ? $funcArgs[1] : array();
					$args[] = !empty($firstFilters) ? new Filtering($firstFilters, $filterArgs, $this, $property, $property->getFiltersAnnotationArgs(0)) : null;
					if (!empty($secondFilters)) {
						$args[] = new Filtering($secondFilters, $filterArgs, $this, $property, $property->getFiltersAnnotationArgs(1));
					}
				}
				$value = call_user_func_array(array($this, $method), $args);
			} else {
				$column = $property->getColumn();
				$value = $this->row->$column;
				if ($value === null) {
					if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}
				} else {
					if (!$property->containsCollection()) {
						$type = $property->getType();
						if (!($value instanceof $type)) {
							throw new InvalidValueException("Property '$name' is expected to contain an instance of $type, instance of " . get_class($value) . " given.");
						}
					} else {
						if (!is_array($value)) {
							throw new InvalidValueException("Property '$name' is expected to contain an array of {$property->getType()} instances.");
						}
					}
				}
			}
		}
		if ($pass !== null) {
			$value = $this->$pass($value);
		}
		return $value;
	}

	/**
	 * Sets value of given property
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidMethodCallException
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 */
	function __set($name, $value)
	{
		$reflection = $this->getCurrentReflection();
		$nativeSetter = $reflection->getSetter('set' . ucfirst($name));
		if ($nativeSetter !== null) {
			$nativeSetter->invoke($this, $value);
		} else {
			$property = $reflection->getEntityProperty($name);
			if ($property === null) {
				throw new MemberAccessException("Cannot access undefined property '$name'.");
			}
			if (!$property->isWritable()) {
				throw new MemberAccessException("Cannot write to read only property '$name'.");
			}
			$customSetter = $property->getSetter();
			if ($customSetter !== null) {
				$customSetterReflection = $reflection->getSetter($customSetter);
				if ($customSetterReflection === null) {
					throw new InvalidMethodCallException("Missing setter method '$customSetter'.");
				}
				$customSetterReflection->invoke($this, $value);
			} else {
				$pass = $property->getSetterPass();
				$column = $property->getColumn();
				if ($value === null) {
					if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}
					$relationship = $property->getRelationship();
					if ($relationship !== null) {
						if (!($relationship instanceof Relationship\HasOne)) {
							throw new InvalidMethodCallException('Only properties with m:hasOne relationship can be set to null.');
						}
					}
				} else {
					if ($property->isBasicType()) {
						if (!settype($value, $property->getType())) {
							throw new InvalidValueException("Cannot convert given value to {$property->getType()}.");
						}
						if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
							throw new InvalidValueException("Given value is not from possible values enumeration.");
						}
					} else {
						$type = $property->getType();
						if (!($value instanceof $type)) {
							throw new InvalidValueException("Unexpected value type given, expected {$property->getType()}.");
						}
						if ($property->hasRelationship()) {
							if (!($value instanceof Entity)) {
								throw new InvalidValueException("Unexpected value type given, expected {$property->getType()}.");
							}
							$relationship = $property->getRelationship();
							if (!($relationship instanceof Relationship\HasOne)) {
								throw new InvalidMethodCallException('Only properties with m:hasOne relationship can be set via magic __set.');
							}
							$column = $relationship->getColumnReferencingTargetTable();
							if ($value->isDetached()) {
								throw new InvalidValueException('Detached entity cannot be assigned to property with relationship.');
							}
							$mapper = $value->mapper; // mapper stealing :)
							$table = $mapper->getTable(get_class($value));
							$idProperty = $mapper->getEntityField($table, $mapper->getPrimaryKey($table));

							$value = $value->$idProperty;
							$this->row->cleanReferencedRowsCache($table, $column);
						} else {
							if (!is_object($value)) {
								throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . gettype($value) . " given.");
							}
						}
					}
				}
				if ($pass !== null) {
					$value = $this->$pass($value);
				}
				$this->row->$column = $value;
			}
		}
	}

	/**
	 * Tells whether given property exists and is not null
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name)
	{
		try {
			return $this->$name !== null;
		} catch (MemberAccessException $e) {
			return false;
		}
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed|void
	 * @throws InvalidMethodCallException
	 * @throws InvalidArgumentException
	 */
	public function __call($name, array $arguments)
	{
		$e = new InvalidMethodCallException("Method '$name' is not callable.");
		if (strlen($name) < 4) {
			throw $e;
		}
		if (substr($name, 0, 3) === 'get') { // get<Name>
			return $this->__get(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 3) === 'set') { // set<Name>
			if (count($arguments) !== 1) {
				throw new InvalidMethodCallException("Method '$name' expects exactly one argument.");
			}
			$this->__set(lcfirst(substr($name, 3)), reset($arguments));

		} elseif (substr($name, 0, 5) === 'addTo' and strlen($name) > 5) { // addTo<Name>
			$this->checkMethodArgumentsCount(1, $arguments, $name);
			$this->addToOrRemoveFrom(self::ACTION_ADD, lcfirst(substr($name, 5)), reset($arguments));

		} elseif (substr($name, 0, 10) === 'removeFrom' and strlen($name) > 10) { // removeFrom<Name>
			$this->checkMethodArgumentsCount(1, $arguments, $name);
			$this->addToOrRemoveFrom(self::ACTION_REMOVE, lcfirst(substr($name, 10)), reset($arguments));

		} elseif (substr($name, 0, 9) === 'removeAll' and strlen($name) > 9) { // removeAll<Name>
			$this->checkMethodArgumentsCount(0, $arguments, $name);
			$property = lcfirst(substr($name, 9));
			foreach ($this->$property as $value) {
				$this->addToOrRemoveFrom(self::ACTION_REMOVE, $property, $value);
			}

		} elseif (substr($name, 0, 10) === 'replaceAll' and strlen($name) > 10) { // replaceAll<Name>
			$this->checkMethodArgumentsCount(1, $arguments, $name);
			$arg = reset($arguments);
			if (!is_array($arg) and (!($arg instanceof Traversable) or ($arg instanceof Entity))) {
				throw new InvalidArgumentException("Argument of method '$name' must contain either array or instance of Traversable which is not Entity.");
			}
			$property = lcfirst(substr($name, 10));
			foreach ($this->$property as $value) {
				$this->addToOrRemoveFrom(self::ACTION_REMOVE, $property, $value);
			}
			$this->addToOrRemoveFrom(self::ACTION_ADD, $property, reset($arguments));

		} else {
			throw $e;
		}
	}

	/**
	 * Performs mass value assignment (using setters)
	 *
	 * @param array|Traversable $values
	 * @param array|null $whitelist
	 * @throws InvalidArgumentException
	 */
	public function assign($values, array $whitelist = null)
	{
		if ($whitelist !== null) {
			$whitelist = array_flip($whitelist);
		}
		if (!is_array($values) and !($values instanceof Traversable)) {
			$type = gettype($values) !== 'object' ? gettype($values) : 'instance of ' . get_class($values);
			throw new InvalidArgumentException("Argument \$values must contain either array or instance of Traversable, $type given.");
		}
		foreach ($values as $property => $value) {
			if ($whitelist === null or isset($whitelist[$property])) {
				$this->__set($property, $value);
			}
		}
	}

	/**
	 * Gets high-level values of properties
	 *
	 * @param array|null $whitelist
	 * @return array
	 */
	public function getData(array $whitelist = null)
	{

		$data = array();
		if ($whitelist !== null) {
			$whitelist = array_flip($whitelist);
		}
		$reflection = $this->getCurrentReflection();
		$usedGetters = array();
		foreach ($reflection->getEntityProperties() as $property) {
			$field = $property->getName();
			if ($whitelist !== null and !isset($whitelist[$field])) {
				continue;
			}
			$data[$field] = $this->__get($property->getName());
			$getter = $property->getGetter();
			if ($getter !== null) {
				$usedGetters[$getter] = true;
			}
		}
		foreach ($reflection->getGetters() as $name => $getter) {
			if (isset($usedGetters[$getter->getName()])) {
				continue;
			}
			$field = lcfirst(substr($name, 3));
			if ($whitelist !== null and !isset($whitelist[$field])) {
				continue;
			}
			if ($getter->getNumberOfRequiredParameters() === 0) {
				$data[$field] = $getter->invoke($this);
			}
		}
		return $data;
	}

	/**
	 * Gets low-level values of underlying Row columns
	 *
	 * @return array
	 */
	public function getRowData()
	{
		return $this->row->getData();
	}

	/**
	 * Gets low-level values of underlying Row columns that were modified
	 *
	 * @return array
	 */
	public function getModifiedRowData()
	{
		return $this->row->getModifiedData();
	}

	/**
	 * Gets current M:N differences
	 *
	 * @return array
	 */
	public function getHasManyRowDifferences()
	{
		$differences = array();
		foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
			if ($property->hasRelationship() and ($property->getRelationship() instanceof Relationship\HasMany)) {
				$relationship = $property->getRelationship();
				$difference = $this->row->createReferencingDataDifference(
					$relationship->getRelationshipTable(),
					$relationship->getColumnReferencingSourceTable(),
					null,
					$relationship->getStrategy()
				);
				if ($difference->mayHaveAny()) {
					$differences[$relationship->getColumnReferencingSourceTable() . ':' . $relationship->getRelationshipTable() . ':' . $relationship->getColumnReferencingTargetTable()] = $difference->getByPivot($relationship->getColumnReferencingTargetTable());
				}
			}
		}
		return $differences;
	}

	/**
	 * Tells whether entity was modified
	 *
	 * @return bool
	 */
	public function isModified()
	{
		return $this->row->isModified();
	}

	/**
	 * Marks entity as non-modified (isModified returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->row->markAsUpdated();
	}

	/**
	 * Marks entity as attached
	 *
	 * @param int $id
	 * @param string $table
	 * @param Connection $connection
	 */
	public function markAsAttached($id, $table, Connection $connection)
	{
		$this->row->markAsAttached($id, $table, $connection);
	}

	/**
	 * Marks entity as detached
	 */
	public function detach()
	{
		$this->row->detach();
	}

	/**
	 * Tells whether entity is in detached state (like newly created entity)
	 *
	 * @return bool
	 */
	public function isDetached()
	{
		return $this->row->isDetached();
	}

	/**
	 * Provides an mapper for entity
	 *
	 * @param IMapper $mapper
	 * @throws InvalidMethodCallException
	 * @throws InvalidStateException
	 */
	public function useMapper(IMapper $mapper)
	{
		if (!$this->isDetached()) {
			throw new InvalidMethodCallException('Mapper can only be provided to detached entity.');
		}
		$newProperties = $this->getReflection($mapper)->getEntityProperties();
		foreach ($this->getCurrentReflection()->getEntityProperties() as $oldProperty) {
			$oldColumn = $oldProperty->getColumn();
			if ($oldColumn !== null) {
				$name = $oldProperty->getName();
				if (!isset($newProperties[$name]) or $newProperties[$name]->getColumn() === null) {
					throw new InvalidStateException('Inconsistent sets of properties detected.');
				}
				if ($this->row->hasColumn($oldColumn)) {
					$newColumn = $newProperties[$name]->getColumn();
					$value = $this->row->$oldColumn;
					unset($this->row->$oldColumn);
					$this->row->$newColumn = $value;
				}
			}
		}
		$this->mapper = $mapper;
		$this->row->setMapper($mapper);
		$this->currentReflection = null;
	}

	/**
	 * Gets current entity's reflection (cached in memory)
	 *
	 * @return EntityReflection
	 */
	protected function getCurrentReflection()
	{
		if ($this->currentReflection === null) {
			$this->currentReflection = $this->getReflection($this->mapper);
		}
		return $this->currentReflection;
	}

	/**
	 * Allows encapsulate set of entities in custom collection
	 *
	 * @param array $entities
	 * @return array
	 */
	protected function createCollection(array $entities)
	{
		return $entities;
	}

	/**
	 * Allows initialize properties' default values
	 */
	protected function initDefaults()
	{
	}

	////////////////////
	////////////////////

	/**
	 * @param Property $property
	 * @param Filtering|null $filtering
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getHasOneValue(Property $property, Filtering $filtering = null)
	{
		$relationship = $property->getRelationship();
		$targetTable = $relationship->getTargetTable();
		$row = $this->row->referenced($targetTable, $relationship->getColumnReferencingTargetTable(), $filtering);
		if ($row === null) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$class = $this->mapper->getEntityClass($targetTable, $row);
			$entity = new $class($row);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Filtering|null $relTableFiltering
	 * @param Filtering|null $targetTableFiltering
	 * @return Entity[]
	 * @throws InvalidValueException
	 */
	private function getHasManyValue(Property $property, Filtering $relTableFiltering = null, Filtering $targetTableFiltering = null)
	{
		$relationship = $property->getRelationship();
		$targetTable = $relationship->getTargetTable();
		$columnReferencingTargetTable = $relationship->getColumnReferencingTargetTable();
		$rows = $this->row->referencing($relationship->getRelationshipTable(), $relationship->getColumnReferencingSourceTable(), $relTableFiltering, $relationship->getStrategy());
		$value = array();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($targetTable, $columnReferencingTargetTable, $targetTableFiltering);
			if ($valueRow !== null) {
				$class = $this->mapper->getEntityClass($targetTable, $valueRow);
				$entity = new $class($valueRow);
				$this->checkConsistency($property, $class, $entity);
				$value[] = $entity;
			}
		}
		return $this->createCollection($value);
	}

	/**
	 * @param Property $property
	 * @param Filtering|null $filtering
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Property $property, Filtering $filtering = null)
	{
		$relationship = $property->getRelationship();
		$targetTable = $relationship->getTargetTable();
		$rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
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
			$class = $this->mapper->getEntityClass($targetTable, $row);
			$entity = new $class($row);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Filtering|null $filtering
	 * @return Entity[]
	 */
	private function getBelongsToManyValue(Property $property, Filtering $filtering = null)
	{
		$relationship = $property->getRelationship();
		$targetTable = $relationship->getTargetTable();
		$rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
		$value = array();
		foreach ($rows as $row) {
			$class = $this->mapper->getEntityClass($targetTable, $row);
			$entity = new $class($row);
			$this->checkConsistency($property, $class, $entity);
			$value[] = $entity;
		}
		return $this->createCollection($value);
	}

	/**
	 * @param string $action
	 * @param string $name
	 * @param mixed $arg
	 * @throws InvalidMethodCallException
	 * @throws InvalidArgumentException
	 * @throws InvalidValueException
	 */
	private function addToOrRemoveFrom($action, $name, $arg)
	{
		if ($this->isDetached()) {
			throw new InvalidMethodCallException('Cannot add or remove related entity to detached entity.');
		}
		if ($arg === null) {
			throw new InvalidArgumentException("Invalid argument given.");
		}
		if (is_array($arg) or ($arg instanceof Traversable and !($arg instanceof Entity))) {
			foreach ($arg as $value) {
				$this->addToOrRemoveFrom($action, $name, $value);
			}
		} else {
			$method = $action === self::ACTION_ADD ? 'addTo' : 'removeFrom';
			$property = $this->getCurrentReflection()->getEntityProperty($name);
			if ($property === null or !$property->hasRelationship() or !($property->getRelationship() instanceof Relationship\HasMany)) {
				throw new InvalidMethodCallException("Cannot call $method method with $name property. Only properties with m:hasMany relationship can be managed this way.");
			}
			if ($property->getFilters()) {
				throw new InvalidMethodCallException("Cannot call $method method with $name property. Only properties with no filters can be managed this way."); // deliberate restriction
			}
			$relationship = $property->getRelationship();
			if ($arg instanceof Entity) {
				if ($arg->isDetached()) {
					throw new InvalidArgumentException('Cannot add or remove detached entity ' . get_class($arg) . '.');
				}
				$type = $property->getType();
				if (!($arg instanceof $type)) {
					throw new InvalidValueException("Unexpected value type: instance of " . $property->getType() . " expected, instance of " . get_class($arg) . " given.");
				}
				$data = $arg->getRowData();
				$arg = $data[$this->mapper->getPrimaryKey($relationship->getTargetTable())];
			}
			$table = $this->mapper->getTable($this->getCurrentReflection()->getName());
			$values = array(
				$relationship->getColumnReferencingSourceTable() => $this->row->{$this->mapper->getPrimaryKey($table)},
				$relationship->getColumnReferencingTargetTable() => $arg,
			);
			$method .= 'Referencing';
			$this->row->$method($values, $relationship->getRelationshipTable(), $relationship->getColumnReferencingSourceTable(), null, $relationship->getStrategy());
		}
	}

	/**
	 * @param Property $property
	 * @param string $mapperClass
	 * @param Entity $entity
	 * @throws InvalidValueException
	 */
	private function checkConsistency(Property $property, $mapperClass, Entity $entity)
	{
		$type = $property->getType();
		if (!($entity instanceof $type)) {
			throw new InvalidValueException("Inconsistency found: property '{$property->getName()}' is supposed to contain an instance of '$type' (due to type hint), but mapper maps it to '$mapperClass'. Please fix getEntityClass method in mapper, property annotation or entities inheritance.");
		}
	}

	/**
	 * @param int $expectedCount
	 * @param array $arguments
	 * @param string $methodName
	 * @throws InvalidMethodCallException
	 */
	private function checkMethodArgumentsCount($expectedCount, array $arguments, $methodName)
	{
		if (count($arguments) !== $expectedCount) {
			if ($expectedCount === 0) {
				throw new InvalidMethodCallException("Method '$methodName' doesn't expect any arguments.");
			} else {
				throw new InvalidMethodCallException("Method '$methodName' expects exactly $expectedCount argument" . ($expectedCount > 1 ? 's' : '') . '.');
			}
		}
	}

}
