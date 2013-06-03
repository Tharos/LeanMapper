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
use LeanMapper\Relationship;

/**
 * Property factory
 *
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
	 * Creates new LeanMapper\Reflection\Property instance from given annotation
	 *
	 * @param string $annotation
	 * @param EntityReflection $reflection
	 * @return Property
	 * @throws InvalidAnnotationException
	 */
	public static function createFromAnnotation($annotation, EntityReflection $reflection)
	{
		$aliases = $reflection->getAliases();

		$matches = array();
		$matched = preg_match('~
			^(null\|)?
			((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)
			(\[\])?
			(\|null)? \s+
			(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)
			(?:\s+\(([^)]+)\))?
			(?:\s+m:(?:(hasOne|hasMany|belongsToOne|belongsToMany)(?:\(([^)]+)\))?))?
			(?:\s+m:filter\(([^)]+)\))?
		~xi', $annotation, $matches);

		if (!$matched) {
			throw new InvalidAnnotationException("Invalid property annotation given: @property $annotation");
		}
		$containsCollection = $matches[3] !== '';
		$isNullable = ($matches[1] !== '' or $matches[4] !== '');

		if ($containsCollection and $isNullable) {
			throw new InvalidAnnotationException("It doesn't make sense to have a property containing collection nullable: @property $annotation");
		}
		$name = substr($matches[5], 1);
		$column = (isset($matches[6]) and $matches[6] !== '') ? $matches[6] : $name;

		$propertyType = new PropertyType($matches[2], $aliases);
		$propertyFilters = null;
		if (isset($matches[9])) {
			if ($propertyType->isBasicType()) {
				throw new InvalidAnnotationException("Invalid property annotation given: {$propertyType->getType()} property cannot be filtered");
			}
			$propertyFilters =  new PropertyFilters($matches[9], $aliases);
		}

		$relationship = null;
		if (isset($matches[7])) {
			$relationship = self::createRelationship(
				$reflection->getName(),
				$propertyType,
				$containsCollection,
				$matches[7],
				isset($matches[8]) ? $matches[8] : null
			);
		}
		if ($relationship !== null and isset($matches[6]) and $matches[6] !== '') {
			throw new InvalidAnnotationException("All special column and table names must be specified in relationship definition when property holds relationship: @property $annotation");
		}

		return new Property(
			$name,
			$column,
			$propertyType,
			$isNullable,
			$containsCollection,
			$relationship,
			$propertyFilters
		);
	}

	////////////////////
	////////////////////

	/**
	 * @param string $sourceClass
	 * @param PropertyType $propertyType
	 * @param bool $containsCollection
	 * @param string $relationshipType
	 * @param string $definition
	 * @return Relationship\BelongsToMany|Relationship\BelongsToOne|Relationship\HasMany|Relationship\HasOne|null
	 * @throws InvalidAnnotationException
	 */
	private static function createRelationship($sourceClass, PropertyType $propertyType, $containsCollection, $relationshipType, $definition = null)
	{
		// logic validation
		if ($propertyType->isBasicType()) {
			throw new InvalidAnnotationException("Invalid property annotation given: {$propertyType->getType()} property cannot have relationship.");
		}
		if ($relationshipType === 'hasMany' or $relationshipType === 'belongsToMany') {
			if (!$containsCollection) {
				throw new InvalidAnnotationException("Invalid property annotation given: property with '$relationshipType' relationship type must contain collection.");
			}
		} else {
			if ($containsCollection) {
				throw new InvalidAnnotationException("Invalid property annotation given: property with '$relationshipType' relationship type must not contain collection.");
			}
		}

		$pieces = array_replace(array_fill(0, 6, ''), $definition !== null ? explode(':', $definition) : array());

		$sourceTable = strtolower(self::trimNamespace($sourceClass));
		$targetTable = strtolower(self::trimNamespace($propertyType->getType()));

		switch ($relationshipType) {
			case 'hasOne':
				return new Relationship\HasOne($pieces[0] ? : $targetTable . '_id', $pieces[1] ? : $targetTable);
			case 'hasMany':
				return new Relationship\HasMany(
					$pieces[0] ? : $sourceTable . '_id',
					$pieces[1] ? : $sourceTable . '_' . $targetTable,
					$pieces[2] ? : $targetTable . '_id',
					$pieces[3] ? : $targetTable
				);
			case 'belongsToOne':
				return new Relationship\BelongsToOne($pieces[0] ? : $sourceTable . '_id', $pieces[1] ? : $targetTable);
			case 'belongsToMany':
				return new Relationship\BelongsToMany($pieces[0] ? : $sourceTable . '_id', $pieces[1] ? : $targetTable);
		}
		return null;
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

}
