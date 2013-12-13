<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Exception\UtilityClassException;
use LeanMapper\IMapper;
use LeanMapper\Relationship;
use LeanMapper\Result;

/**
 * @author Vojtěch Kohout
 */
class PropertyFactory
{

	/**
	 * @throws UtilityClassException
	 */
	public function __construct()
	{
		throw new UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
	}

	/**
	 * Creates new Property instance from given annotation
	 *
	 * @param string $annotationType
	 * @param string $annotation
	 * @param EntityReflection $entityReflection
	 * @param IMapper|null $mapper
	 * @return Property
	 * @throws InvalidAnnotationException
	 */
	public static function createFromAnnotation($annotationType, $annotation, EntityReflection $entityReflection, IMapper $mapper = null)
	{
		$aliases = $entityReflection->getAliases();

		$matches = array();
		$matched = preg_match('~
			^(null\|)?
			((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)
			(\[\])?
			(\|null)?\s+
			(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)
			(?:\s+=\s*(?:
				"((?:\\\\"|[^"])+)" |  # double quoted string
				\'((?:\\\\\'|[^\'])+)\' |  # single quoted string
				([^ ]*))  # unquoted value
			)?
			(?:\s+\(([^)]+)\))?
			(?:\s+(.*)\s*)?
		~xi', $annotation, $matches);

		if (!$matched) {
			throw new InvalidAnnotationException("Invalid property definition given: @$annotationType $annotation in entity {$entityReflection->getName()}.");
		}

		$propertyType = new PropertyType($matches[2], $aliases);
		$isWritable = $annotationType === 'property';
		$containsCollection = $matches[3] !== '';
		$isNullable = ($matches[1] !== '' or $matches[4] !== '');
		$name = substr($matches[5], 1);

		$hasDefaultValue = false;
		$defaultValue = null;
		if (isset($matches[6]) and $matches[6] !== '') {
			$defaultValue = str_replace('\"', '"', $matches[6]);
		} elseif (isset($matches[7]) and $matches[7] !== '') {
			$defaultValue = str_replace("\\'", "'", $matches[7]);
		} elseif (isset($matches[8]) and $matches[8] !== '') {
			$defaultValue = $matches[8];
		}
		if ($defaultValue !== null) {
			$hasDefaultValue = true;
			try {
				$defaultValue = self::fixDefaultValue($defaultValue, $propertyType, $isNullable);
			} catch (InvalidAnnotationException $e) {
				throw new InvalidAnnotationException("Invalid property definition given: @$annotationType $annotation in entity {$entityReflection->getName()}, " . lcfirst($e->getMessage()));
			}
		}
		$column = $mapper !== null ? $mapper->getColumn($entityReflection->getName(), $name) : $name;
		if (isset($matches[9]) and $matches[9] !== '') {
			$column = $matches[9];
		}

		$relationship = null;
		$propertyMethods = null;
		$propertyFilters = null;
		$propertyPasses = null;
		$propertyValuesEnum = null;
		$customFlags = array();

		if (isset($matches[10])) {
			$flagMatches = array();
			preg_match_all('~m:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*(?:\(([^)]*)\))?~', $matches[10], $flagMatches, PREG_SET_ORDER);
			foreach ($flagMatches as $match) {
				$flag = $match[1];
				$flagArgument = (isset($match[2]) and $match[2] !== '') ? $match[2] : null;

				switch ($flag) {
					case 'hasOne':
					case 'hasMany':
					case 'belongsToOne':
					case 'belongsToMany':
						if ($relationship !== null) {
							throw new InvalidAnnotationException("It doesn't make sense to have multiple relationship definitions in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$relationship = self::createRelationship(
							$entityReflection->getName(),
							$propertyType,
							$containsCollection,
							$flag,
							$flagArgument,
							$mapper
						);
						break;
					case 'useMethods':
						if ($propertyMethods !== null) {
							throw new InvalidAnnotationException("Multiple m:useMethods flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$propertyMethods = new PropertyMethods($name, $isWritable, $flagArgument);
						break;
					case 'filter':
						if ($propertyFilters !== null) {
							throw new InvalidAnnotationException("Multiple m:filter flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$propertyFilters =  new PropertyFilters($flagArgument);
						break;
					case 'passThru':
						if ($propertyPasses !== null) {
							throw new InvalidAnnotationException("Multiple m:passThru flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$propertyPasses = new PropertyPasses($flagArgument);
						break;
					case 'enum':
						if ($propertyValuesEnum !== null) {
							throw new InvalidAnnotationException("Multiple values enumerations found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						if ($flagArgument === null) {
							throw new InvalidAnnotationException("Parameter of m:enum flag was not found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$propertyValuesEnum = new PropertyValuesEnum($flagArgument, $entityReflection);
						break;
					default:
						if (array_key_exists($flag, $customFlags)) {
							throw new InvalidAnnotationException("Multiple m:$flag flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}.");
						}
						$customFlags[$flag] = $flagArgument;
				}
			}
		}
		if ($relationship !== null) {
			$column = null;
			if ($relationship instanceof Relationship\HasOne) {
				$column = $relationship->getColumnReferencingTargetTable();
			}
		}
		return new Property(
			$name,
			$entityReflection,
			$column,
			$propertyType,
			$isWritable,
			$isNullable,
			$containsCollection,
			$hasDefaultValue,
			$defaultValue,
			$relationship,
			$propertyMethods,
			$propertyFilters,
			$propertyPasses,
			$propertyValuesEnum,
			$customFlags
		);
	}

	////////////////////
	////////////////////

	/**
	 * @param string $sourceClass
	 * @param PropertyType $propertyType
	 * @param bool $containsCollection
	 * @param string $relationshipType
	 * @param string|null $definition
	 * @param IMapper|null $mapper
	 * @return mixed
	 * @throws InvalidAnnotationException
	 */
	private static function createRelationship($sourceClass, PropertyType $propertyType, $containsCollection, $relationshipType, $definition = null, IMapper $mapper = null)
	{
		if ($relationshipType !== 'hasOne') {
			$strategy = Result::STRATEGY_IN; // default strategy
			if ($definition !== null and substr($definition, -6) === '#union') {
				$strategy = Result::STRATEGY_UNION;
				$definition = substr($definition, 0, -6);
			}
		}
		$pieces = array_replace(array_fill(0, 6, ''), $definition !== null ? explode(':', $definition) : array());

		$sourceTable = ($mapper !== null ? $mapper->getTable($sourceClass) : null);
		$targetTable = ($mapper !== null ? $mapper->getTable($propertyType->getType()) : null);

		switch ($relationshipType) {
			case 'hasOne':
				$relationshipColumn = ($mapper !== null ? $mapper->getRelationshipColumn($sourceTable, $targetTable) : self::getSurrogateRelationshipColumn($propertyType));
				return new Relationship\HasOne($pieces[0] ?: $relationshipColumn, $pieces[1] ?: $targetTable);
			case 'hasMany':
				return new Relationship\HasMany(
					$pieces[0] ?: ($mapper !== null ? $mapper->getRelationshipColumn($mapper->getRelationshipTable($sourceTable, $targetTable), $sourceTable) : null),
					$pieces[1] ?: ($mapper !== null ? $mapper->getRelationshipTable($sourceTable, $targetTable) : null),
					$pieces[2] ?: ($mapper !== null ? $mapper->getRelationshipColumn($mapper->getRelationshipTable($sourceTable, $targetTable), $targetTable) : null),
					$pieces[3] ?: $targetTable,
					$strategy
				);
			case 'belongsToOne':
				$relationshipColumn = ($mapper !== null ? $mapper->getRelationshipColumn($targetTable, $sourceTable) : $sourceTable);
				return new Relationship\BelongsToOne($pieces[0] ?: $relationshipColumn, $pieces[1] ?: $targetTable, $strategy);
			case 'belongsToMany':
				$relationshipColumn = ($mapper !== null ? $mapper->getRelationshipColumn($targetTable, $sourceTable) : $sourceTable);
				return new Relationship\BelongsToMany($pieces[0] ?: $relationshipColumn, $pieces[1] ?: $targetTable, $strategy);
		}
		return null;
	}

	/**
	 * @param PropertyType $propertyType
	 * @return string
	 */
	private static function getSurrogateRelationshipColumn(PropertyType $propertyType)
	{
		return strtolower(self::trimNamespace($propertyType->getType())) . '{hasOne:' . self::generateRandomString(10) . '}';
	}

	/**
	 * @param string $class
	 * @return string
	 */
	private static function trimNamespace($class)
	{
		$class = explode('\\', $class);
		return end($class);
	}

	/**
	 * @param int $length
	 * @return string
	 */
	private static function generateRandomString($length)
	{
		return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
	}

	/**
	 * @param mixed $value
	 * @param PropertyType $propertyType
	 * @param bool $isNullable
	 * @return mixed
	 * @throws InvalidAnnotationException
	 */
	private static function fixDefaultValue($value, PropertyType $propertyType, $isNullable)
	{
		if ($isNullable and strtolower($value) === 'null') {
			return null;
		} elseif (!$propertyType->isBasicType()) {
			throw new InvalidAnnotationException('Only properties of basic types may have default values specified.');
		}
		switch ($propertyType->getType()) {
			case 'boolean':
				$lower = strtolower($value);
				if ($lower !== 'true' and $lower !== 'false') {
					throw new InvalidAnnotationException("Property of type boolean cannot have default value '$value'.");
				}
				return $lower === 'true';
			case 'integer':
				if (!is_numeric($value)) {
					throw new InvalidAnnotationException("Property of type integer cannot have default value '$value'.");
				}
				return intval($value, 0);
			case 'float':
				if (!is_numeric($value)) {
					throw new InvalidAnnotationException("Property of type float cannot have default value '$value'.");
				}
				return floatval($value);
			case 'array':
				if (strtolower($value) !== 'array()' and $value !== '[]') {
					throw new InvalidAnnotationException("Property of type array cannot have default value '$value'.");
				}
				return array();
			case 'string':
				if ($value === '\'\'' or $value === '""') {
					return '';
				}
				return $value;
			default:
				return $value;
		}
	}

}
