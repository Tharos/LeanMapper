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

use Exception;
use LeanMapper\Exception\Exception as LeanMapperException;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Exception\InvalidMethodCallException;
use LeanMapper\Exception\InvalidStateException;
use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Exception\MemberAccessException;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Relationship;
use ReflectionException;
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

    /** @var IMapper|null */
    protected $mapper;

    /** @var IEntityFactory|null */
    protected $entityFactory;

    /** @var array<string, array<string, EntityReflection>> */
    protected static $reflections = [];

    /** @var EntityReflection|null */
    private $currentReflection;


    /**
     * Gets reflection of current entity
     */
    public static function getReflection(?IMapper $mapper = null): EntityReflection
    {
        $class = get_called_class();
        $mapperClass = $mapper !== null ? get_class($mapper) : '';
        if (!isset(static::$reflections[$class][$mapperClass])) {
            static::$reflections[$class][$mapperClass] = new EntityReflection($class, $mapper, static::getReflectionProvider());
        }
        return static::$reflections[$class][$mapperClass];
    }


    protected static function getReflectionProvider(): IEntityReflectionProvider
    {
        static $reflectionProvider = null;

        if ($reflectionProvider === null) {
            $reflectionProvider = new DefaultEntityReflectionProvider;
        }

        return $reflectionProvider;
    }


    /**
     * @param Row|iterable<string, mixed>|null $arg
     * @throws InvalidArgumentException
     */
    public function __construct($arg = null)
    {
        if ($arg instanceof Row) {
            if ($arg->isDetached()) {
                throw new InvalidArgumentException(
                    'It is not allowed to create entity ' . get_called_class() . ' from detached instance of ' . Row::class . '.'
                );
            }
            $this->row = $arg;
            $this->mapper = $arg->getMapper();
        } else {
            $this->row = Result::createDetachedInstance()->getRow();
            foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
                if ($property->hasDefaultValue()) {
                    $propertyName = $property->getName();
                    $this->set($propertyName, $property->getDefaultValue());
                }
            }
            $this->initDefaults();
            if ($arg !== null) {
                if (!is_iterable($arg)) {
                    $type = Helpers::getType($arg);
                    throw new InvalidArgumentException(
                        "Argument \$arg in " . get_called_class(
                        ) . "::__construct must contain either null, array, instance of " . Row::class . " or instance of " . Traversable::class . ", $type given."
                    );
                }
                $this->assign($arg);
            }
        }
    }


    /**
     * @return mixed
     * @throws InvalidMethodCallException
     * @throws MemberAccessException
     */
    public function __get(string $name)
    {
        $reflection = $this->getCurrentReflection();
        $nativeGetter = $reflection->getGetter('get' . ucfirst($name));
        if ($nativeGetter !== null) {
            try {
                return $nativeGetter->invoke($this); // filters arguments are not relevant here
            } catch (ReflectionException $e) {
                throw new MemberAccessException("Cannot invoke native getter of property '$name' in entity " . get_called_class() . '.');
            }
        }
        $property = $reflection->getEntityProperty($name);
        if ($property === null) {
            throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
        }
        $customGetter = $property->getGetter();
        if ($customGetter !== null) {
            if (!method_exists($this, $customGetter)) {
                throw new InvalidMethodCallException("Missing getter method '$customGetter' in entity " . get_called_class() . '.');
            }
            return $this->$customGetter(); // filters arguments are not relevant here
        }
        return $this->get($property);
    }


    /**
     * @param mixed $value
     * @throws InvalidMethodCallException
     * @throws MemberAccessException
     */
    public function __set(string $name, $value): void
    {
        $reflection = $this->getCurrentReflection();
        $nativeSetter = $reflection->getSetter('set' . ucfirst($name));
        if ($nativeSetter !== null) {
            try {
                $nativeSetter->invoke($this, $value);
                return;
            } catch (ReflectionException $e) {
                throw new MemberAccessException("Cannot invoke native setter of property '$name' in entity " . get_called_class() . '.');
            }
        }
        $property = $reflection->getEntityProperty($name);
        if ($property === null) {
            throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
        }
        if (!$property->isWritable()) {
            throw new MemberAccessException("Cannot write to read-only property '$name' in entity " . get_called_class() . '.');
        }
        $customSetter = $property->getSetter();
        if ($customSetter !== null) {
            if (!method_exists($this, $customSetter)) {
                throw new InvalidMethodCallException("Missing setter method '$customSetter' in entity " . get_called_class() . '.');
            }
            $this->$customSetter($value);
            return;
        }
        $this->set($property, $value);
    }


    /**
     * Tells whether given property exists and is not null
     *
     * @throws LeanMapperException
     */
    public function __isset(string $name): bool
    {
        try {
            return $this->__get($name) !== null;
        } catch (MemberAccessException $e) {
            return false;
        } catch (LeanMapperException $e) {
            if ($this->isDetached() and $e->getCode() === Result::ERROR_MISSING_COLUMN) {
                return false;
            }
            throw $e;
        }
    }


    /**
     * @param  array<mixed> $arguments
     * @return mixed|void
     * @throws InvalidMethodCallException
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments)
    {
        $e = new InvalidMethodCallException("Method $name in entity " . get_called_class() . ' is not callable.');
        if (strlen($name) < 4) {
            throw $e;
        }
        if (substr($name, 0, 3) === 'get') { // get<Name>
            return $this->get(lcfirst(substr($name, 3)), $arguments);

        } elseif (substr($name, 0, 3) === 'set') { // set<Name>
            if (count($arguments) !== 1) {
                throw new InvalidMethodCallException("Method $name in entity " . get_called_class() . ' expects exactly one argument.');
            }
            $property = $this->getCurrentReflection()->getEntityProperty(
                $propertyName = lcfirst(substr($name, 3))
            );
            if ($property === null) {
                throw new MemberAccessException("Cannot access undefined property '$propertyName' in entity " . get_called_class() . '.');
            }
            if (!$property->isWritable()) {
                throw new MemberAccessException("Cannot write to read-only property '$propertyName' in entity " . get_called_class() . '.');
            }
            $this->set($property, reset($arguments));

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
                throw new InvalidArgumentException(
                    "Argument of method $name in entity " . get_called_class(
                    ) . ' must contain either array or instance of ' . Traversable::class . ' which is not ' . Entity::class . '.'
                );
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
     * @param  iterable<string, mixed> $values
     * @param  array<string>|null $whitelist
     * @throws InvalidArgumentException
     */
    public function assign(iterable $values, ?array $whitelist = null): void
    {
        if ($whitelist !== null) {
            $whitelist = array_flip($whitelist);
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
     * @param  array<string>|null $whitelist
     * @return array<string, mixed>
     */
    public function getData(?array $whitelist = null): array
    {
        $data = [];
        if ($whitelist !== null) {
            $whitelist = array_flip($whitelist);
        }
        $reflection = $this->getCurrentReflection();
        $usedGetters = [];
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
     * @return array<string, mixed>
     */
    public function getRowData(): array
    {
        return $this->row->getData();
    }


    /**
     * Gets low-level values of underlying Row columns that were modified
     *
     * @return array<string, mixed>
     */
    public function getModifiedRowData(): array
    {
        return $this->row->getModifiedData();
    }


    /**
     * Gets current M:N differences
     *
     * @return array<string, array<mixed, int>>
     */
    public function getHasManyRowDifferences(): array
    {
        $differences = [];
        foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
            if ($property->hasRelationship() and ($property->getRelationship() instanceof Relationship\HasMany)) {
                $relationship = $property->getRelationship();
                if (!$relationship->hasRelationshipTable()) {
                    throw new InvalidStateException('Cannot get hasMany differences from detached entity.');
                }
                $difference = $this->row->createReferencingDataDifference(
                    $relationship->getRelationshipTable(),
                    $relationship->getColumnReferencingSourceTable(),
                    null,
                    $relationship->getStrategy()
                );
                if ($difference->mayHaveAny()) {
                    $differences[$relationship->getColumnReferencingSourceTable() . ':' . $relationship->getRelationshipTable(
                    ) . ':' . $relationship->getColumnReferencingTargetTable()] = $difference->getByPivot(
                        $relationship->getColumnReferencingTargetTable()
                    );
                }
            }
        }
        return $differences;
    }


    /**
     * Tells whether entity was modified
     */
    public function isModified(): bool
    {
        return $this->row->isModified();
    }


    /**
     * Marks entity as non-modified (isModified returns false right after this method call)
     */
    public function markAsUpdated(): void
    {
        $this->row->markAsUpdated();
        foreach ($this->getCurrentReflection()->getEntityProperties() as $property) {
            if ($property->hasRelationship() and ($property->getRelationship() instanceof Relationship\HasMany)) {
                $relationship = $property->getRelationship();
                $this->row->cleanReferencingAddedAndRemovedMeta(
                    $relationship->getRelationshipTable(),
                    $relationship->getColumnReferencingSourceTable(),
                    null,
                    $relationship->getStrategy()
                );
            }
        }
    }


    /**
     * Detaches entity
     */
    public function detach(): void
    {
        $this->row->detach();
        $this->entityFactory = null;
        $this->mapper = null;
    }


    /**
     * Attaches entity
     *
     * @param  int|string $id
     * @throws InvalidStateException
     */
    public function attach($id): void
    {
        if ($this->mapper === null) {
            throw new InvalidStateException('Missing mapper in ' . get_called_class() . '.');
        }
        $this->row->attach($id, $this->mapper->getTable(get_called_class()));
    }


    /**
     * Tells whether entity is in detached state (like newly created entity)
     */
    public function isDetached(): bool
    {
        return $this->row->isDetached();
    }


    /**
     * Provides dependencies
     *
     * @throws InvalidArgumentException
     * @throws InvalidStateException
     */
    public function makeAlive(?IEntityFactory $entityFactory = null, ?Connection $connection = null, ?IMapper $mapper = null): void
    {
        $entityFactory === null or $this->setEntityFactory($entityFactory);
        $mapper === null or $this->useMapper($mapper);
        $connection === null or $this->row->setConnection($connection);

        if ($this->entityFactory === null) {
            throw new InvalidStateException('Missing entity factory in entity ' . get_called_class() . '.');
        }
        if ($this->mapper === null) {
            throw new InvalidStateException('Missing mapper in entity ' . get_called_class() . '.');
        }
        if (!$this->row->hasConnection()) {
            throw new InvalidStateException('Missing connection in Result in entity ' . get_called_class() . '.');
        }
    }


    /**
     * @return array
     */
    public function __sleep()
    {
        return ['row', 'mapper', 'entityFactory'];
    }


    /**
     * @param Property|string $property
     * @param array<mixed> $filterArgs
     * @throws InvalidValueException
     * @throws InvalidStateException
     * @throws MemberAccessException
     * @return mixed
     */
    protected function get($property, array $filterArgs = [])
    {
        if ($property instanceof Property) {
            $name = $property->getName();
        } else {
            $name = $property;
            $property = $this->getCurrentReflection()->getEntityProperty($name);
            if ($property === null) {
                throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
            }
        }
        $pass = $property->getGetterPass();
        if ($property->isBasicType()) {
            $column = $property->getColumn();
            try {
                $value = $this->row->__get($column);
            } catch (LeanMapperException $e) {
                if (!$property->isNullable()) {
                    throw new InvalidStateException(
                        "Cannot get value of property '{$property->getName()}' in entity " . get_called_class(
                        ) . ' due to low-level failure: ' . $e->getMessage(), $e->getCode(), $e
                    );
                }
                $value = null;
            }
            $value = $this->decodeRowValue($value, $property);
            if ($pass !== null) {
                $value = $this->$pass($value);
            }
            if ($value === null) {
                if (!$property->isNullable()) {
                    throw new InvalidValueException("Property $name in entity " . get_called_class() . ' cannot be null.');
                }
                return $value;
            }
            if ($pass !== null) {
                return $value;
            }

            settype($value, $property->getType());
            if ($property->containsEnumeration() and !$property->isValueFromEnum($value)) {
                throw new InvalidValueException(
                    "Given value is not from possible values enumeration in property '{$property->getName()}' in entity " . get_called_class() . '.'
                );
            }
            return $value;
        } // property doesn't contain basic type
        if ($property->hasRelationship()) {
            if ($this->entityFactory) {
                $implicitFilters = $this->createImplicitFilters($property->getType(), new Caller($this, $property));
                $firstFilters = $property->getFilters(0) ?: [];
                $secondFilters = [];

                $relationship = $property->getRelationship();
                if ($relationship instanceof Relationship\HasMany) {
                    $secondFilters = $this->mergeFilters($property->getFilters(1) ?: [], $implicitFilters->getFilters());
                } else {
                    $firstFilters = $this->mergeFilters($firstFilters, $implicitFilters->getFilters());
                }
                if (!empty($firstFilters) or !empty($secondFilters)) {
                    if ($relationship instanceof Relationship\HasMany) {
                        $relationshipTableFiltering = !empty($firstFilters) ? new Filtering(
                            $firstFilters,
                            $filterArgs,
                            $this,
                            $property,
                            (array)$property->getFiltersTargetedArgs(0)
                        ) : null;
                        $targetTableFiltering = new Filtering(
                            $secondFilters,
                            $filterArgs,
                            $this,
                            $property,
                            array_merge($implicitFilters->getTargetedArgs(), (array)$property->getFiltersTargetedArgs(1))
                        );
                    } else {
                        $targetTableFiltering = !empty($firstFilters) ? new Filtering(
                            $firstFilters,
                            $filterArgs,
                            $this,
                            $property,
                            array_merge($implicitFilters->getTargetedArgs(), (array)$property->getFiltersTargetedArgs(0))
                        ) : null;
                    }
                }
            }
            try {
                return $this->getValueByPropertyWithRelationship(
                    $property,
                    isset($targetTableFiltering) ? $targetTableFiltering : null,
                    isset($relationshipTableFiltering) ? $relationshipTableFiltering : null
                );
            } catch (LeanMapperException $e) {
                throw new InvalidStateException($e->getMessage(), $e->getCode(), $e);
            }
        } // property doesn't contain basic type and doesn't contain relationship
        $column = $property->getColumn();
        try {
            $value = $this->row->__get($column);
        } catch (LeanMapperException $e) {
            throw new LeanMapperException(
                "Cannot get value of property '{$property->getName()}' in entity " . get_called_class(
                ) . ' due to low-level failure: ' . $e->getMessage(), $e->getCode(), $e
            );
        }
        $value = $this->decodeRowValue($value, $property);
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
                throw new InvalidValueException(
                    "Property '$name' in entity " . get_called_class() . " is expected to contain an instance of $type, " . Helpers::getType($value) . " given."
                );
            }
            return $value;
        }
        if (!is_array($value)) {
            throw new InvalidValueException(
                "Property '$name' in entity " . get_called_class() . " is expected to contain an array of {$property->getType()} instances."
            );
        }
        return $value;
    }


    /**
     * @param Property|string $property
     * @param mixed $value
     * @throws InvalidMethodCallException
     * @throws InvalidValueException
     * @throws MemberAccessException
     */
    protected function set($property, $value): void
    {
        if ($property instanceof Property) {
            $name = $property->getName();
        } else {
            $name = $property;
            $property = $this->getCurrentReflection()->getEntityProperty($name);
            if ($property === null) {
                throw new MemberAccessException("Cannot access undefined property '$name' in entity " . get_called_class() . '.');
            }
        }
        if ($value === null and !$property->isNullable()) {
            throw new InvalidValueException("Property '$name' in entity " . get_called_class() . ' cannot be null.');
        }
        $pass = $property->getSetterPass();
        $column = $property->getColumn();

        if ($property->isBasicType()) {
            if ($pass !== null) {
                $value = $this->$pass($value);
            } elseif ($value !== null && !Helpers::isType($value, $property->getType())) {
                $givenType = Helpers::getType($value);
                throw new InvalidValueException(
                    "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class() . ", {$property->getType()} expected, $givenType given."
                );
            }
            if ($value !== null and $property->containsEnumeration() and !$property->isValueFromEnum($value)) {
                throw new InvalidValueException(
                    "Given value is not from possible values enumeration in property '{$property->getName()}' in entity " . get_called_class() . '.'
                );
            }
            $this->row->$column = $this->encodeRowValue($value, $property);
            return;
        }
        // property doesn't contain basic type
        $type = $property->getType();
        $givenType = Helpers::getType($value);

        if ($property->hasRelationship()) {
            if ($value !== null) {
                if (!($value instanceof $type)) {
                    throw new InvalidValueException(
                        "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class(
                        ) . ", {$property->getType()} expected, $givenType given."
                    );
                }
                if (!($value instanceof Entity)) {
                    throw new InvalidValueException(
                        "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class(
                        ) . ", " . Entity::class. " expected, $givenType given."
                    );
                }
                if ($value->isDetached()) { // the value should be entity
                    throw new InvalidValueException(
                        "Detached entity cannot be assigned to property '{$property->getName()}' with relationship in entity " . get_called_class(
                        ) . '.'
                    );
                }
            }
            $this->assignEntityToProperty($value, $name); // $pass is irrelevant relevant here
            return;
        }
        //property doesn't contain basic type and property doesn't contain relationship
        if ($property->containsCollection()) {
            if (!is_array($value)) {
                throw new InvalidValueException(
                    "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class(
                    ) . ", array of {$property->getType()} expected, $givenType given."
                );
            }
            $this->row->$column = $this->encodeRowValue($pass !== null ? $this->$pass($value) : $value, $property);
            return;
        }
        if ($value !== null and !($value instanceof $type)) {
            throw new InvalidValueException(
                "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class(
                ) . ", {$property->getType()} expected, $givenType given."
            );
        }
        $this->row->$column = $this->encodeRowValue($pass !== null ? $this->$pass($value) : $value, $property);
    }


    /**
     * Gets current entity's reflection (cached in memory)
     */
    protected function getCurrentReflection(): EntityReflection
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
    protected function getValueByPropertyWithRelationship(
        $property,
        Filtering $targetTableFiltering = null,
        Filtering $relationshipTableFiltering = null
    ) {
        if (is_string($property)) {
            $property = $this->getCurrentReflection()->getEntityProperty($property);
        }
        $relationship = $property->getRelationship();
        if ($relationship === null) {
            throw new InvalidArgumentException("Property '{$property->getName()}' in entity " . get_called_class() . " has no relationship.");
        }
        $method = explode('\\', get_class($relationship));
        $method = 'get' . end($method) . 'Value';
        try {
            return $this->$method($property, $relationship, $targetTableFiltering, $relationshipTableFiltering);
        } catch (Exception $e) {
            throw new LeanMapperException(
                "Cannot get value of property '{$property->getName()}' in entity " . get_called_class(
                ) . ' due to low-level failure: ' . $e->getMessage(), $e->getCode(), $e
            );
        }
    }


    /**
     * @param Property|string $property micro-optimalization
     * @throws InvalidMethodCallException
     */
    protected function assignEntityToProperty(?Entity $entity, $property):void
    {
        if ($entity !== null) {
            $this->useMapper($entity->mapper);
            $this->setEntityFactory($entity->entityFactory);
        }
        if (is_string($property)) {
            $property = $this->getCurrentReflection()->getEntityProperty($property);
        }
        $relationship = $property->getRelationship();
        if (!($relationship instanceof Relationship\HasOne)) {
            throw new InvalidMethodCallException(
                "Cannot assign value to property '{$property->getName()}' in entity " . get_called_class(
                ) . '. Only properties with m:hasOne relationship can be set via magic __set.'
            );
        }
        $column = $relationship->getColumnReferencingTargetTable();

        if ($entity !== null) {
            $row = $entity->row;
            $pkColumn = $this->mapper->getPrimaryKey(
                $this->mapper->getTable(get_class($entity))
            );
            $this->row->$column = $row->$pkColumn;
            $this->row->setReferencedRow($row, $column);
        } else {
            $this->row->$column = null;
            $this->row->setReferencedRow(null, $column);
        }
    }


    /**
     * Called after value is read from Row
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function decodeRowValue($value, Property $property)
    {
        return $value;
    }


    /**
     * Called before value is passed to Row
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function encodeRowValue($value, Property $property)
    {
        return $value;
    }


    protected function createImplicitFilters(string $entityClass, ?Caller $caller = null): ImplicitFilters
    {
        $implicitFilters = $this->mapper->getImplicitFilters($entityClass, $caller);
        return ($implicitFilters instanceof ImplicitFilters) ? $implicitFilters : new ImplicitFilters($implicitFilters);
    }


    /**
     * @param  array<string|\Closure> $filters1
     * @param  array<string|\Closure> $filters2
     * @return array<string|\Closure>
     */
    protected function mergeFilters(array $filters1, array $filters2): array
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
     * Allows initialize properties' default values
     */
    protected function initDefaults(): void
    {
    }

    ////////////////////
    ////////////////////

    /**
     * @throws InvalidValueException
     */
    private function getHasOneValue(Property $property, Relationship\HasOne $relationship, ?Filtering $filtering = null): ?Entity
    {
        if (!$relationship->hasTargetTable()) {
            $viaColumn = $relationship->getColumnReferencingTargetTable();

            if ($this->row->hasReferencedRow($viaColumn) && $this->row->getReferencedRow($viaColumn) === null && $property->isNullable()) {
                return null;
            }

            throw new InvalidStateException('Cannot get referenced Entity for detached Entity.');
        }
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
     * @return Entity[]
     * @throws InvalidValueException
     */
    private function getHasManyValue(
        Property $property,
        Relationship\HasMany $relationship,
        ?Filtering $targetTableFiltering = null,
        ?Filtering $relTableFiltering = null
    ) {
        if (!$relationship->hasRelationshipTable()) {
            throw new InvalidStateException('Cannot get related Entities for detached Entity.');
        }
        $targetTable = $relationship->getTargetTable();
        $columnReferencingTargetTable = $relationship->getColumnReferencingTargetTable();
        $rows = $this->row->referencing(
            $relationship->getRelationshipTable(),
            $relationship->getColumnReferencingSourceTable(),
            $relTableFiltering,
            $relationship->getStrategy()
        );
        $value = [];
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
     * @throws InvalidValueException
     */
    private function getBelongsToOneValue(Property $property, Relationship\BelongsToOne $relationship, ?Filtering $filtering = null): ?Entity
    {
        if (!$relationship->hasTargetTable()) {
            throw new InvalidStateException('Cannot get referenced Entity for detached Entity.');
        }
        $targetTable = $relationship->getTargetTable();
        $rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
        $count = count($rows);
        if ($count > 1) {
            throw new InvalidValueException(
                'There cannot be more than one entity referencing to entity ' . get_called_class(
                ) . " in property '{$property->getName()}' with m:belongToOne relationship."
            );
        } elseif ($count === 0) {
            if (!$property->isNullable()) {
                $name = $property->getName();
                throw new InvalidValueException("Property '$name' cannot be null in entity " . get_called_class() . '.');
            }
            return null;
        } else {
            $row = reset($rows);
            /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/2142 */
            $entityClass = $this->mapper->getEntityClass($targetTable, $row);
            /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/2142 */
            $entity = $this->entityFactory->createEntity($entityClass, $row);
            $this->checkConsistency($property, $entityClass, $entity);
            $entity->makeAlive($this->entityFactory);
            return $entity;
        }
    }


    /**
     * @return Entity[]
     */
    private function getBelongsToManyValue(Property $property, Relationship\BelongsToMany $relationship, ?Filtering $filtering = null)
    {
        if (!$relationship->hasTargetTable()) {
            throw new InvalidStateException('Cannot get related Entities for detached Entity.');
        }
        $targetTable = $relationship->getTargetTable();
        $rows = $this->row->referencing($targetTable, $relationship->getColumnReferencingSourceTable(), $filtering, $relationship->getStrategy());
        $value = [];
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
     * @throws InvalidMethodCallException
     * @throws InvalidStateException
     */
    private function useMapper(IMapper $mapper): void
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
        } elseif ($this->mapper != $mapper) { // intentionally !=, we want to ensure that types and states are same
            throw new InvalidStateException("Given mapper isn't same as mapper already present in entity " . get_called_class() . '.');
        }
    }


    /**
     * @throws InvalidStateException
     */
    private function setEntityFactory(IEntityFactory $entityFactory): void
    {
        if ($this->entityFactory === null) {
            $this->entityFactory = $entityFactory;
        } elseif ($this->entityFactory != $entityFactory) { // intentionally !=, we want to ensure that types and states are same
            throw new InvalidStateException(
                "Given entity factory isn't same as entity factory already present in entity " . get_called_class() . '.'
            );
        }
    }


    /**
     * @param mixed $arg
     * @throws InvalidMethodCallException
     * @throws InvalidArgumentException
     * @throws InvalidValueException
     */
    private function addToOrRemoveFrom(string $action, string $name, $arg): void
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
                throw new InvalidMethodCallException(
                    "Cannot call $method method with '$name' property in entity " . get_called_class(
                    ) . '. Only properties with m:hasMany relationship can be managed this way.'
                );
            }
            if ($property->getFilters()) {
                throw new InvalidMethodCallException(
                    "Cannot call $method method with '$name' property in entity " . get_called_class(
                    ) . '. Only properties without filters can be managed this way.'
                ); // deliberate restriction
            }
            $relationship = $property->getRelationship();
            if ($arg instanceof Entity) {
                if ($arg->isDetached()) {
                    throw new InvalidArgumentException(
                        'Cannot add or remove detached entity ' . get_class($arg) . " to $name in entity " . get_called_class() . '.'
                    );
                }
                $type = $property->getType();
                if (!($arg instanceof $type)) {
                    $type = Helpers::getType($arg);
                    throw new InvalidValueException(
                        "Unexpected value type given in property '{$property->getName()}' in entity " . get_called_class(
                        ) . ", {$property->getType()} expected, $type given."
                    );
                }
                $data = $arg->getRowData();
                $arg = $data[$this->mapper->getPrimaryKey($relationship->getTargetTable())];
            }
            $table = $this->mapper->getTable($this->getCurrentReflection()->getName());
            $values = [
                $relationship->getColumnReferencingSourceTable() => $this->row->{$this->mapper->getPrimaryKey($table)},
                $relationship->getColumnReferencingTargetTable() => $arg,
            ];
            $method .= 'Referencing';
            $this->row->$method(
                $values,
                $relationship->getRelationshipTable(),
                $relationship->getColumnReferencingSourceTable(),
                null,
                $relationship->getStrategy()
            );
        }
    }


    /**
     * @throws InvalidValueException
     */
    private function checkConsistency(Property $property, string $mapperClass, Entity $entity): void
    {
        $type = $property->getType();
        if (!($entity instanceof $type)) {
            throw new InvalidValueException(
                "Inconsistency found: property '{$property->getName()}' in entity " . get_called_class(
                ) . " is supposed to contain an instance of '$type' (due to type hint), but mapper maps it to '$mapperClass'. Please fix getEntityClass method in mapper, property annotation or entities inheritance."
            );
        }
    }


    /**
     * @param  array<mixed> $arguments
     * @throws InvalidMethodCallException
     */
    private function checkMethodArgumentsCount(int $expectedCount, array $arguments, string $methodName): void
    {
        if (count($arguments) !== $expectedCount) {
            if ($expectedCount === 0) {
                throw new InvalidMethodCallException("Method '$methodName' in entity " . get_called_class() . " doesn't expect any arguments.");
            } else {
                throw new InvalidMethodCallException(
                    "Method '$methodName' in entity " . get_called_class(
                    ) . " expects exactly $expectedCount argument" . ($expectedCount > 1 ? 's' : '') . '.'
                );
            }
        }
    }

}
