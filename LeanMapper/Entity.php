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

use Exception;
use LeanMapper\Exception\Exception as LeanMapperException;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Exception\RuntimeException;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
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

	const SCOPE_GET = 'get';

	const SCOPE_SET = 'set';

	/** @var Row */
	protected $row;

	/** @var IMapper */
	protected $mapper;

	/** @var IEntityFactory */
	protected $entityFactory;

	/** @var EntityReflection[] */
	protected static $reflections = array();

	/** @var EntityReflection */
	private $currentReflection;

	/** @var array */
	private $scope = array(
		self::SCOPE_GET => array(),
		self::SCOPE_GET => array()
	);


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
				throw new InvalidArgumentException('It is not allowed to create entity ' . get_called_class() . ' from detached instance of LeanMapper\Row.');
			}
			$this->row = $arg;
			$this->mapper = $arg->getMapper();
		} else {
			$this->row = Result::createDetachedInstance()->getRow();
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
					throw new InvalidArgumentException("Argument \$arg in " . get_called_class() . "::__construct must contain either null, array, instance of LeanMapper\\Row or instance of Traversable, $type given.");
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
	 * @throws InvalidStateException
	 * @throws LeanMapperException
	 */
	public function __get($name /*, array $filterArgs*/)
	{
		$reflection = $this->getCurrentReflection();
		$nativeGetter = $reflection->getGetter('get' . ucfirst($name));
		if ($nativeGetter !== null and !$this->isInScope(self::SCOPE_GET, $name)) {
			$this->enterScope(self::SCOPE_GET, $name);
			$value = $nativeGetter->invoke($this); // filters are not relevant here
			$this->leaveScope(self::SCOPE_GET, $name);
			return $value;
		}
		$property = $reflection->getEntityProperty($name);
		if ($property === null) {
			throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
		}
		$customGetter = $property->getGetter();
		if ($customGetter !== null and !$this->isInScope(self::SCOPE_GET, $name)) {
			if (!method_exists($this, $customGetter)) {
				throw new InvalidMethodCallException("Missing getter method '$customGetter' in entity " . get_called_class() . '.');
			}
			$this->enterScope(self::SCOPE_GET, $name);
			$value = $this->$customGetter(); // filters are not relevant here
			$this->leaveScope(self::SCOPE_GET, $name);
			return $value;
		}
		$pass = $property->getGetterPass();
		if ($property->isBasicType()) {
			$column = $property->getColumn();
			try {
				$value = $this->row->$column;
			} catch (LeanMapperException $e) {
				throw new LeanMapperException("Cannot get value of property '{$property->getName()}' in entity " . get_called_class() . ' due to low-level failure: ' . lcfirst($e->getMessage()));
			}
			if ($pass !== null) {
				$value = $this->$pass($value);
			}
			if ($value === null) {
				if (!$property->isNullable()) {
					throw new InvalidValueException("Property $name in entity " . get_called_class() . ' cannot be null.');
				}
				return $value;
			}
			if ($property->containsCollection()) {
				if (!is_array($value)) {
					throw new InvalidValueException("Property '$name' in entity " . get_called_class() . " is expected to contain an array of {$property->getType()} values.");
				}
				return $value;
			}
			settype($value, $property->getType());
			if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
				throw new InvalidValueException("Given value is not from possible values enumeration in property '{$property->getName()}' in entity " . get_called_class() . '.');
			}
			return $value;
		} // property doesn't contain basic type
		if ($property->hasRelationship()) {
			if ($this->entityFactory === null) {
				throw new InvalidStateException('Missing entity factory in ' . get_called_class() . '.');
			}
			$implicitFilters = $this->createImplicitFilters($property->getType(), new Caller($this, $property));
			$firstFilters = $property->getFilters(0) ?: array();

			$relationship = $property->getRelationship();
			if ($relationship instanceof Relationship\HasMany) {
				$secondFilters = $this->mergeFilters($property->getFilters(1) ?: array(), $implicitFilters->getFilters());
			} else {
				$firstFilters = $this->mergeFilters($firstFilters, $implicitFilters->getFilters());
			}
			if (!empty($firstFilters) or !empty($secondFilters)) {
				$funcArgs = func_get_args();
				$filterArgs = isset($funcArgs[1]) ? $funcArgs[1] : array();

				if ($relationship instanceof Relationship\HasMany) {
					$relationshipTableFiltering = !empty($firstFilters) ? new Filtering($firstFilters, $filterArgs, $this, $property, (array) $property->getFiltersTargetedArgs(0)) : null;
					$targetTableFiltering = new Filtering($secondFilters, $filterArgs, $this, $property, array_merge($implicitFilters->getTargetedArgs(), (array) $property->getFiltersTargetedArgs(1)));
				} else {
					$targetTableFiltering = !empty($firstFilters) ? new Filtering($firstFilters, $filterArgs, $this, $property, array_merge($implicitFilters->getTargetedArgs(), (array) $property->getFiltersTargetedArgs(0))) : null;
				}
			}
			$value = $this->getValueByPropertyWithRelationship($property, isset($targetTableFiltering) ? $targetTableFiltering : null, isset($relationshipTableFiltering) ? $relationshipTableFiltering : null);
			if ($pass !== null) {
				$value = $this->$pass($value);
			}
			return $value;
		} // property doesn't contain basic type and doesn't contain relationship
		$column = $property->getColumn();
		try {
			$value = $this->row->$column;
		} catch (LeanMapperException $e) {
			throw new LeanMapperException("Cannot get value of property '{$property->getName()}' in entity " . get_called_class() . ' due to low-level failure: ' . lcfirst($e->getMessage()));
		}
		if ($pass !== null) {
			$value = $this->$pass($value);
		}
		if ($value === null) {
			if (!$property->isNullable()) {
				throw new InvalidValueException("Property '$name' in entity " . get_called_class() . " cannot be null.");
			}
			return $value;
		} // property doesn't contain basic type, doesn't contain relationship and doesn't contain null
		if (!$property->containsCollection()) {
			$type = $property->getType();
			if (!($value instanceof $type)) {
				throw new InvalidValueException("Property '$name' in entity " . get_called_class() . " is expected to contain an instance of $type, instance of " . get_class($value) . " given.");
			}
			return $value;
		}
		if (!is_array($value)) {
			throw new InvalidValueException("Property '$name' in entity " . get_called_class() . " is expected to contain an array of {$property->getType()} instances.");
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
	public function __set($name, $value)
	{
		$reflection = $this->getCurrentReflection();
		$nativeSetter = $reflection->getSetter('set' . ucfirst($name));
		if ($nativeSetter !== null and !$this->isInScope(self::SCOPE_SET, $name)) {
			$this->enterScope(self::SCOPE_SET, $name);
			$nativeSetter->invoke($this, $value);
			$this->leaveScope(self::SCOPE_SET, $name);
			return;
		}
		$property = $reflection->getEntityProperty($name);
		if ($property === null) {
			throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
		}
		if (!$property->isWritable()) {
			throw new MemberAccessException("Cannot write to read-only property '$name' in entity " . get_called_class() . '.');
		}
		$customSetter = $property->getSetter();
		if ($customSetter !== null and !$this->isInScope(self::SCOPE_SET, $name)) {
			if (!method_exists($this, $customSetter)) {
				throw new InvalidMethodCallException("Missing setter method '$customSetter' in entity " . get_called_class() . '.');
			}
			$this->enterScope(self::SCOPE_SET, $name);
			$this->$customSetter($value);
			$this->leaveScope(self::SCOPE_SET, $name);
			return;
		}
		if (($pass = $property->getSetterPass()) !== null) {
			$value = $this->$pass($value);
		}
		$column = $property->getColumn();
		if ($value === null) {
			if (!$property->isNullable()) {
				throw new InvalidValueException("Property '$name' in entity " . get_called_class() . ' cannot be null.');
			}
			$relationship = $property->getRelationship();
			if ($relationship !== null) {
				if (!($relationship instanceof Relationship\HasOne)) {
					throw new InvalidMethodCallException("Property '$name' in entity " . get_called_class() . " cannot be null since it contains relationship that doesn't support it.");
				}
			}
			$this->row->$column = $value;
			return;
		} // value is not null
		$givenType = gettype($value) !== 'object' ? gettype($value) : 'instance of ' . get_class($value);
		if ($property->isBasicType()) {
			if ($property->containsCollection()) {
				if (!is_array($value)) {
					throw new InvalidValueException("Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", array of {$property->getType()} expected, $givenType given.");
				}
				$this->row->$column = $value;
				return;
			}
			settype($value, $property->getType());
			if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
				throw new InvalidValueException("Given value is not from possible values enumeration in property '{$property->getName()}' in entity " . get_called_class() . '.');
			}
			$this->row->$column = $value;
			return;
		} // value is not null and property doesn't contain basic type
		$type = $property->getType();
		if ($property->hasRelationship()) {
			if (!($value instanceof $type)) {
				throw new InvalidValueException("Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", {$property->getType()} expected, $givenType given.");
			}
			if ($value->isDetached()) { // we are sure that the value is entity
				throw new InvalidValueException("Detached entity cannot be assigned to property '{$property->getName()}' with relationship in entity " . get_called_class() . '.');
			}
			$this->assignEntityToProperty($value, $name);
			return;
		} // value is not null, property doesn't contain basic type and property doesn't contain relationship
		if ($property->containsCollection()) {
			if (!is_array($value)) {
				throw new InvalidValueException("Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", array of {$property->getType()} expected, $givenType given.");
			}
			$this->row->$column = $value;
			return;
		}
		if (!($value instanceof $type)) {
			throw new InvalidValueException("Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", {$property->getType()} expected, $givenType given.");
		}
		$this->row->$column = $value;
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
		} catch (Exception $e) {
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
		$e = new InvalidMethodCallException("Method $name in entity " . get_called_class() . ' is not callable.');
		if (strlen($name) < 4) {
			throw $e;
		}
		if (substr($name, 0, 3) === 'get') { // get<Name>
			return $this->__get(lcfirst(substr($name, 3)), $arguments);

		} elseif (substr($name, 0, 3) === 'set') { // set<Name>
			if (count($arguments) !== 1) {
				throw new InvalidMethodCallException("Method $name in entity " . get_called_class() . ' expects exactly one argument.');
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
				throw new InvalidArgumentException("Argument of method $name in entity " . get_called_class() . ' must contain either array or instance of Traversable which is not Entity.');
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
			$givenType = gettype($values) !== 'object' ? gettype($values) : 'instance of ' . get_class($values);
			throw new InvalidArgumentException("Argument \$values in " . get_called_class() . "::assign must contain either array or instance of Traversable, $givenType given.");
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
	 * Detaches entity
	 */
	public function detach()
	{
		$this->row->detach();
		$this->mapper = null;
	}

	/**
	 * Attaches entity
	 *
	 * @param int $id
	 * @throws InvalidStateException
	 */
	public function attach($id)
	{
		if ($this->mapper === null) {
			throw new InvalidStateException('Missing mapper in ' . get_called_class() . '.');
		}
		$this->row->attach($id, $this->mapper->getTable(get_called_class()));
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
	 * Provides dependencies
	 *
	 * @param IEntityFactory|null $entityFactory
	 * @param Connection|null $connection
	 * @param IMapper|null $mapper
	 * @throws InvalidArgumentException
	 * @throws InvalidStateException
	 */
	public function makeAlive(IEntityFactory $entityFactory = null, Connection $connection = null, IMapper $mapper = null)
	{
		$entityFactory === null or $this->setEntityFactory($entityFactory);
		$mapper === null or $this->useMapper($mapper);
		$connection === null or $this->row->setConnection($connection);

		if ($this->entityFactory === null) {
			throw new InvalidStateException('Missing entity factory in entity ' . get_called_class() . '.');
		}
		if ($this->mapper === null) {
			throw new InvalidStateException('Missing entity factory in entity ' . get_called_class() . '.');
		}
		if (!$this->row->hasConnection()) {
			throw new InvalidStateException('Missing connection in Result in entity ' . get_called_class() . '.');
		}
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
	 * @param Property|string $property micro-optimalization
	 * @param Filtering|null $targetTableFiltering
	 * @param Filtering|null $relationshipTableFiltering
	 * @throws LeanMapperException
	 * @return Entity|Entity[]
	 */
	protected function getValueByPropertyWithRelationship($property, Filtering $targetTableFiltering = null, Filtering $relationshipTableFiltering = null)
	{
		if (is_string($property)) {
			$property = $this->getCurrentReflection()->getEntityProperty($property);
		}
		$relationship = $property->getRelationship();
		$method = explode('\\', get_class($relationship));
		$method = 'get' . end($method) . 'Value';
		try {
			return $this->$method($property, $relationship, $targetTableFiltering, $relationshipTableFiltering);
		} catch (Exception $e) {
			throw new LeanMapperException("Cannot get value of property '{$property->getName()}' in entity " . get_called_class() . ' due to low-level failure: ' . lcfirst($e->getMessage()));
		}
	}

	/**
	 * @param Entity|null $entity
	 * @param Property|string $property micro-optimalization
	 * @throws InvalidMethodCallException
	 */
	protected function assignEntityToProperty(Entity $entity = null, $property)
	{
		if ($entity !== null) {
			$this->useMapper($entity->mapper);
			$this->setEntityFactory($entity->entityFactory);
			$table = $this->mapper->getTable(get_class($entity));
			$idProperty = $this->mapper->getEntityField($table, $this->mapper->getPrimaryKey($table));
		}
		if (is_string($property)) {
			$property = $this->getCurrentReflection()->getEntityProperty($property);
		}
		$relationship = $property->getRelationship();
		if (!($relationship instanceof Relationship\HasOne)) {
			throw new InvalidMethodCallException("Cannot assign value to property '{$property->getName()}' in entity " . get_called_class() . '. Only properties with m:hasOne relationship can be set via magic __set.');
		}
		$column = $relationship->getColumnReferencingTargetTable();
		if ($entity !== null) {
			$this->row->setReferencedRow($entity->row, $column);
			$this->row->$column = $entity->$idProperty;
		} else {
			$this->row->$column = null;
		}
	}

	/**
	 * @param string $entityClass
	 * @param Caller $caller
	 * @return ImplicitFilters
	 */
	protected function createImplicitFilters($entityClass, Caller $caller = null)
	{
		$implicitFilters = $this->mapper->getImplicitFilters($entityClass, $caller);
		return ($implicitFilters instanceof ImplicitFilters) ? $implicitFilters : new ImplicitFilters($implicitFilters);
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
	 * @param Relationship\HasOne $relationship micro-optimalization
	 * @param Filtering|null $filtering
	 * @throws InvalidValueException
	 * @return Entity
	 */
	private function getHasOneValue(Property $property, Relationship\HasOne $relationship, Filtering $filtering = null)
	{
		$targetTable = $relationship->getTargetTable();
		$row = $this->row->referenced($targetTable, $relationship->getColumnReferencingTargetTable(), $filtering);
		if ($row === null) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null in entity " . get_called_class() . '.');
			}
			return null;
		} else {
			$entityClass = $this->mapper->getEntityClass($targetTable, $row);
			$entity = $this->entityFactory->createEntity($entityClass, $row);
			$this->checkConsistency($property, $entityClass, $entity);
			$entity->makeAlive($this->entityFactory);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * * @param Relationship\HasMany $relationship micro-optimalization
	 * @param Filtering|null $targetTableFiltering
	 * @param Filtering|null $relTableFiltering
	 * @return Entity[]
	 * @throws InvalidValueException
	 */
	private function getHasManyValue(Property $property, Relationship\HasMany $relationship, Filtering $targetTableFiltering = null, Filtering $relTableFiltering = null)
	{
		$targetTable = $relationship->getTargetTable();
		$columnReferencingTargetTable = $relationship->getColumnReferencingTargetTable();
		$rows = $this->row->referencing($relationship->getRelationshipTable(), $relationship->getColumnReferencingSourceTable(), $relTableFiltering, $relationship->getStrategy());
		$value = array();
		foreach ($rows as $row) {
			$valueRow = $row->referenced($targetTable, $columnReferencingTargetTable, $targetTableFiltering);
			if ($valueRow !== null) {
				$entityClass = $this->mapper->getEntityClass($targetTable, $valueRow);
				$entity = $this->entityFactory->createEntity($entityClass, $valueRow);
				$this->checkConsistency($property, $entityClass, $entity);
				$entity->makeAlive($this->entityFactory);
				$value[] = $entity;
			}
		}
		return $this->entityFactory->createCollection($value);
	}

	/**
	 * @param Property $property
	 * @param Relationship\BelongsToOne $relationship micro-optimalization
	 * @param Filtering|null $filtering
	 * @return Entity
	 * @throws InvalidValueException
	 */
	private function getBelongsToOneValue(Property $property, Relationship\BelongsToOne $relationship, Filtering $filtering = null)
	{
		$targetTable = $relationship->getTargetTable();
		$rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
		$count = count($rows);
		if ($count > 1) {
			throw new InvalidValueException('There cannot be more than one entity referencing to entity ' . get_called_class() . " in property '{$property->getName()}' with m:belongToOne relationship.");
		} elseif ($count === 0) {
			if (!$property->isNullable()) {
				$name = $property->getName();
				throw new InvalidValueException("Property '$name' cannot be null in entity " . get_called_class() . '.');
			}
			return null;
		} else {
			$row = reset($rows);
			$entityClass = $this->mapper->getEntityClass($targetTable, $row);
			$entity = $this->entityFactory->createEntity($entityClass, $row);
			$this->checkConsistency($property, $entityClass, $entity);
			$entity->makeAlive($this->entityFactory);
			return $entity;
		}
	}

	/**
	 * @param Property $property
	 * @param Relationship\BelongsToMany $relationship micro-optimalization
	 * @param Filtering|null $filtering
	 * @return Entity[]
	 */
	private function getBelongsToManyValue(Property $property, Relationship\BelongsToMany $relationship, Filtering $filtering = null)
	{
		$targetTable = $relationship->getTargetTable();
		$rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
		$value = array();
		foreach ($rows as $row) {
			$entityClass = $this->mapper->getEntityClass($targetTable, $row);
			$entity = $this->entityFactory->createEntity($entityClass, $row);
			$this->checkConsistency($property, $entityClass, $entity);
			$entity->makeAlive($this->entityFactory);
			$value[] = $entity;
		}
		return $this->entityFactory->createCollection($value);
	}

	/**
	 * Provides an mapper for entity
	 *
	 * @param IMapper $mapper
	 * @throws InvalidMethodCallException
	 * @throws InvalidStateException
	 */
	private function useMapper(IMapper $mapper)
	{
		if ($this->mapper === null) {
			$newProperties = $this->getReflection($mapper)->getEntityProperties();
			foreach ($this->getCurrentReflection()->getEntityProperties() as $oldProperty) {
				$oldColumn = $oldProperty->getColumn();
				if ($oldColumn !== null) {
					$name = $oldProperty->getName();
					if (!isset($newProperties[$name]) or $newProperties[$name]->getColumn() === null) {
						throw new InvalidStateException('Inconsistent sets of properties detected in entity ' . get_called_class() . '.');
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
		} elseif ($this->mapper !== $mapper) {
			throw new InvalidStateException("Given mapper doesn't equal to mapper already present in entity " . get_called_class() . '.');
		}
	}

	/**
	 * @param IEntityFactory $entityFactory
	 * @throws InvalidStateException
	 */
	private function setEntityFactory(IEntityFactory $entityFactory)
	{
		if ($this->entityFactory === null) {
			$this->entityFactory = $entityFactory;
		} elseif ($this->entityFactory !== $entityFactory) {
			throw new InvalidStateException("Given entity factory doesn't equal to entity factory already present in entity " . get_called_class() . '.');
		}
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
			throw new InvalidArgumentException('Invalid argument given in entity ' . get_called_class() . '.');
		}
		if (is_array($arg) or ($arg instanceof Traversable and !($arg instanceof Entity))) {
			foreach ($arg as $value) {
				$this->addToOrRemoveFrom($action, $name, $value);
			}
		} else {
			$method = $action === self::ACTION_ADD ? 'addTo' : 'removeFrom';
			$property = $this->getCurrentReflection()->getEntityProperty($name);
			if ($property === null or !$property->hasRelationship() or !($property->getRelationship() instanceof Relationship\HasMany)) {
				throw new InvalidMethodCallException("Cannot call $method method with '$name' property in entity " . get_called_class() . '. Only properties with m:hasMany relationship can be managed this way.');
			}
			if ($property->getFilters()) {
				throw new InvalidMethodCallException("Cannot call $method method with '$name' property in entity " . get_called_class() . '. Only properties without filters can be managed this way.'); // deliberate restriction
			}
			$relationship = $property->getRelationship();
			if ($arg instanceof Entity) {
				if ($arg->isDetached()) {
					throw new InvalidArgumentException('Cannot add or remove detached entity ' . get_class($arg) . " to $name in entity " . get_called_class() . '.');
				}
				$type = $property->getType();
				if (!($arg instanceof $type)) {
					$type = gettype($arg) !== 'object' ? gettype($arg) : 'instance of ' . get_class($arg);
					throw new InvalidValueException("Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", {$property->getType()} expected, $type given.");
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
			throw new InvalidValueException("Inconsistency found: property '{$property->getName()}' in entity " . get_called_class() . " is supposed to contain an instance of '$type' (due to type hint), but mapper maps it to '$mapperClass'. Please fix getEntityClass method in mapper, property annotation or entities inheritance.");
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
				throw new InvalidMethodCallException("Method '$methodName' in entity " . get_called_class() . " doesn't expect any arguments.");
			} else {
				throw new InvalidMethodCallException("Method '$methodName' in entity " . get_called_class() . " expects exactly $expectedCount argument" . ($expectedCount > 1 ? 's' : '') . '.');
			}
		}
	}

	/**
	 * @param array $filters1
	 * @param array $filters2
	 * @return array
	 */
	private function mergeFilters(array $filters1, array $filters2)
	{
		if (!empty($filters2)) {
			foreach (array_reverse($filters2) as $filter) {
				if (!in_array($filter, $filters1)) {
					array_unshift($filters1, $filter);
				}
			}
		}
		return $filters1;
	}

	/**
	 * @param string $type self::SCOPE_GET|self::SCOPE_SET
	 * @param string $propertyName
	 * @return bool
	 */
	private function isInScope($type, $propertyName)
	{
		return isset($this->scope[$type][$propertyName]);
	}

	/**
	 * @param string $type self::SCOPE_GET|self::SCOPE_SET
	 * @param string $propertyName
	 */
	private function enterScope($type, $propertyName)
	{
		$this->scope[$type][$propertyName] = true;
	}

	/**
	 * @param string $type self::SCOPE_GET|self::SCOPE_SET
	 * @param string $propertyName
	 */
	private function leaveScope($type, $propertyName)
	{
		unset($this->scope[$type][$propertyName]);
	}

}
