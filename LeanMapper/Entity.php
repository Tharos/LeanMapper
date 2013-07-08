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
use ReflectionMethod;
use Traversable;

/**
 * Base class for custom entities
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

	/** @var array */
	private $internalGetters = array('getData', 'getRowData', 'getModifiedRowData', 'getCurrentReflection', 'getReflection', 'getHasManyRowDifferences', 'getEntityClass');


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
			// TODO: call fields initialization that would use default values from annotations
			$this->initDefaults();
			if ($arg !== null) {
				if (!is_array($arg) and !($arg instanceof Traversable)) {
					throw new InvalidArgumentException('Argument $arg in entity constructor must be either null, array, instance of LeanMapper\Row or instance of Traversable, ' . gettype($arg) . ' given.');
				}
				$this->assign($arg);
			}
		}
	}

	/**
	 * Returns value of given field
	 *
	 * @param string $name
	 * @return mixed
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 * @throws RuntimeException
	 */
	public function __get($name/*, array $filterArgs*/)
	{
		$property = $this->getCurrentReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'get' . ucfirst($name);
			$internalGetters = array_flip($this->internalGetters);
			if (method_exists($this, $method) and !isset($internalGetters[$method])) {  // TODO: find better solution (using reflection?)
				return call_user_func(array($this, $method)); // $filterArgs are not relevant here
			}
			throw new MemberAccessException("Undefined property: $name");
		}
		$column = $property->getColumn();
		if ($property->isBasicType()) {
			$value = $this->row->$column;
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				return null;
			}
			if (!settype($value, $property->getType())) {
				throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
			}
			if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
				throw new InvalidValueException("Value '$value' is not from possible values enumeration.");
			}
		} else {
			if ($property->hasRelationship()) {

				$filter = ($set = $property->getFilters(0)) ? $this->getFilterCallback($set, func_get_args()) : null;
				$relationship = $property->getRelationship();

				$method = explode('\\', get_class($relationship));
				$method = 'get' . array_pop($method) . 'Value';
				$args = array($property, $filter);

				if ($method === 'getHasManyValue') {
					$args[] = ($set = $property->getFilters(1)) ? $this->getFilterCallback($set, func_get_args()) : null;
				}
				$value = call_user_func_array(array($this, $method), $args);

			} else {
				$value = $this->row->$column;
				if ($value === null) {
					if (!$property->isNullable()) {
						throw new InvalidValueException("Property '$name' cannot be null.");
					}
					return null;
				}
				if (!$property->containsCollection()) {
					$type = $property->getType();
					if (!($value instanceof $type)) {
						throw new InvalidValueException("Property '$name' is expected to contain an instance of '$type', instance of '" . get_class($value) . "' given.");
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
	 * Sets value of given field
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws InvalidMethodCallException
	 * @throws InvalidValueException
	 * @throws MemberAccessException
	 */
	function __set($name, $value)
	{
		$property = $this->getCurrentReflection()->getEntityProperty($name);
		if ($property === null) {
			$method = 'set' . ucfirst($name);
			if (method_exists($this, $method)) { // TODO: find better solution (using reflection)
				call_user_func(array($this, $method), $value);
			} else {
				throw new MemberAccessException("Undefined property: $name");
			}
		} else {
			if (!$property->isWritable()) {
				throw new MemberAccessException("Cannot write to read only property '$name'.");
			}
			$column = $property->getColumn();
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property '$name' cannot be null.");
				}
				$this->row->$column = null;
			} else {
				if ($property->isBasicType()) {
					if (!settype($value, $property->getType())) {
						throw new InvalidValueException("Cannot convert value '$value' to " . $property->getType() . '.');
					}
					if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
						throw new InvalidValueException("Value '$value' is not from possible values enumeration.");
					}
					$this->row->$column = $value;
				} else {
					$type = $property->getType();
					if (!($value instanceof $type)) {
						throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . get_class($value) . " given.");
					}
					if ($property->hasRelationship()) {
						if (!($value instanceof Entity)) {
							throw new InvalidValueException("Only entities can be set via magic __set on field with relationships.");
						}
						$relationship = $property->getRelationship();
						if (!($relationship instanceof Relationship\HasOne)) {
							throw new InvalidMethodCallException('Only fields with m:hasOne relationship can be set via magic __set.');
						}
						$column = $relationship->getColumnReferencingTargetTable();
						$table = $relationship->getTargetTable();

						if ($value->isDetached()) {
							throw new InvalidValueException('Detached entity must be stored in database before use in relationships.');
						}
						$this->row->$column = $value->id;
						$this->row->cleanReferencedRowsCache($table, $column);
					} else {
						if (!is_object($value)) {
							throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . gettype($value) . " given.");
						}
						$this->row->$column = $value;
					}
				}
			}
		}
	}

	/**
	 * Calls __get() or __set() method when get<$name> or set<$name> methods don't exist
	 *
	 * @param string $name
	 * @param array $arguments
	 * @param array $arguments
	 * @return mixed
	 * @throws InvalidMethodCallException
	 */
	public function __call($name, array $arguments)
	{
		$e = new InvalidMethodCallException("Method '$name' is not callable.");
		if (strlen($name) < 4) {
			throw $e;
		}
		if (substr($name, 0, 3) === 'get') {
			return $this->__get(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 3) === 'set') {
			$this->__set(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 5) === 'addTo' and strlen($name) > 5) {
			$this->addToOrRemoveFrom(self::ACTION_ADD, lcfirst(substr($name, 5)), array_shift($arguments));

		} elseif (substr($name, 0, 10) === 'removeFrom' and strlen($name) > 10) {
			$this->addToOrRemoveFrom(self::ACTION_REMOVE, lcfirst(substr($name, 10)), array_shift($arguments));

		} else {
			throw $e;
		}
	}

	/**
	 * Performs a mass value assignment (using setters)
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
			throw new InvalidArgumentException('Argument $values must be either array or instance of Traversable, ' . gettype($values) . ' given.');
		}
		foreach ($values as $field => $value) {
			if ($whitelist === null or isset($whitelist[$field])) {
				$this->__set($field, $value);
			}
		}
	}

	/**
	 * Returns array of high-level fields with values
	 *
	 * @return array
	 */
	public function getData()
	{
		$data = array();
		foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
			$data[$property->getName()] = $this->__get($property->getName());
		}
		$internalGetters = array_flip($this->internalGetters);
		foreach ($this->getCurrentReflection()->getMethods(ReflectionMethod::IS_PUBLIC) as $method) { // TODO: better support from EntityReflection
			$name = $method->getName();
			if (substr($name, 0, 3) === 'get' and !isset($internalGetters[$name])) {
				if ($method->getNumberOfRequiredParameters() === 0) {
					$data[lcfirst(substr($name, 3))] = $method->invoke($this);
				}
			}
		}
		return $data;
	}

	/**
	 * Returns array of low-level fields with values
	 *
	 * @return array
	 */
	public function getRowData()
	{
		return $this->row->getData();
	}

	/**
	 * Returns array of modified low-level fields with new values
	 *
	 * @return array
	 */
	public function getModifiedRowData()
	{
		return $this->row->getModifiedData();
	}

	/**
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
					null,
					$relationship->getColumnReferencingSourceTable(),
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
	 * Tells whether entity is in modified state
	 *
	 * @return bool
	 */
	public function isModified()
	{
		return $this->row->isModified();
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
	 * Marks entity as detached (it means non-persisted)
	 */
	public function detach()
	{
		$this->row->detach();
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
			if ($oldProperty->getColumn() !== null) {
				$name = $oldProperty->getName();
				if (!isset($newProperties[$name]) or $newProperties[$name]->getColumn() === null) {
					throw new InvalidStateException('Inconsistent sets of properties.');
				}
				if (isset($this->row->$name)) {
					$newName = $newProperties[$name]->getColumn();
					$value = $this->row->$name;
					unset($this->row->$name);
					$this->row->$newName = $value;
				}
			}
		}
		$this->mapper = $mapper;
		$this->row->setMapper($mapper);
		$this->currentReflection = null;
	}

	/**
	 * Marks entity as persisted
	 *
	 * @param int $id
	 * @param string $table
	 * @param DibiConnection $connection
	 */
	public function markAsCreated($id, $table, DibiConnection $connection)
	{
		$this->row->markAsCreated($id, $table, $connection);
	}

	/**
	 * Marks entity as non-updated (isModified() returns false right after this method call)
	 */
	public function markAsUpdated()
	{
		$this->row->markAsUpdated();
	}

	/**
	 * @param IMapper|null $mapper
	 * @return EntityReflection
	 */
	protected static function getReflection(IMapper $mapper = null)
	{
		$class = get_called_class();
		$mapperClass = $mapper !== null ? get_class($mapper) : '';
		if (!isset(static::$reflections[$class][$mapperClass])) {
			static::$reflections[$class][$mapperClass] = new EntityReflection($class, $mapper);
		}

		return static::$reflections[$class][$mapperClass];
	}

	/**
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
	 * @param array $entities
	 * @return array
	 */
	protected function createCollection(array $entities)
	{
		return $entities;
	}

	protected function initDefaults()
	{
	}

	/**
	 * @param Property $property
	 * @param Row $row
	 * @return string
	 */
	protected function getEntityClass(Property $property, Row $row = null)
	{
		return $this->mapper->getEntityClass($property->getRelationship()->getTargetTable(), $row);
	}

	////////////////////
	////////////////////

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getHasOneValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$row = $this->row->referenced($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingTargetTable());
		if ($row === null) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null.");
			}
			return null;
		} else {
			$class = $this->getEntityClass($property, $row);
			$entity = new $class($row, $this->mapper);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $relTableFilter
	 * @param Closure|null $targetTableFilter
	 * @return Entity[]
	 * @throws InvalidValueException
	 */
	private function getHasManyValue(Property $property, Closure $relTableFilter = null, Closure $targetTableFilter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getRelationshipTable(), $relTableFilter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
		$value = array();
		$type = $property->getType();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($relationship->getTargetTable(), $targetTableFilter, $relationship->getColumnReferencingTargetTable());
			if ($valueRow !== null) {
				$class = $this->getEntityClass($property, $valueRow);
				$entity = new $class($valueRow, $this->mapper);
				$this->checkConsistency($property, $class, $entity);
				$value[] = $entity;
			}
		}
		return $this->createCollection($value);
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
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
			$class = $this->getEntityClass($property, $row);
			$entity = new $class($row, $this->mapper);
			$this->checkConsistency($property, $class, $entity);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Closure|null $filter
	 * @return Entity[]
	 */
	private function getBelongsToManyValue(Property $property, Closure $filter = null)
	{
		$relationship = $property->getRelationship();
		$rows = $this->row->referencing($relationship->getTargetTable(), $filter, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
		$value = array();
		foreach ($rows as $row) {
			$class = $this->getEntityClass($property, $row);
			$entity = new $class($row, $this->mapper);
			$this->checkConsistency($property, $class, $entity);
			$value[] = $entity;
		}
		return $this->createCollection($value);
	}

	/**
	 * @param array $propertyFilters
	 * @param array $filterArgs
	 * @return callable|null
	 */
	private function getFilterCallback(array $propertyFilters, array $filterArgs)
	{
		$filterCallback = null;
		if (!empty($propertyFilters)) {
			$filterArgs = isset($filterArgs[1]) ? $filterArgs[1] : array();
			$filterCallback = function (DibiFluent $statement) use ($propertyFilters, $filterArgs) {
				foreach ($propertyFilters as $propertyFilter) {
					call_user_func_array($propertyFilter, array_merge(array($statement), $filterArgs));
				}
			};
		}
		return $filterCallback;
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
		$method = $action === self::ACTION_ADD ? 'addTo' : 'removeFrom';
		if ($arg === null) {
			throw new InvalidArgumentException("Invalid argument given.");
		}
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
				throw new InvalidValueException("Unexpected value type: " . $property->getType() . " expected, " . get_class($arg) . " given.");
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
		$this->row->$method($values, $relationship->getRelationshipTable(), null, $relationship->getColumnReferencingSourceTable(), $relationship->getStrategy());
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
			throw new InvalidValueException("Inconsistency found: property '{$property->getName()}' is supposed to contain an instance of '$type' (due to type hint), but mapper maps it to '$mapperClass'. Please fix getEntityClass() method in mapper, property annotation or entities inheritance.");
		}
	}

}
