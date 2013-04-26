<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Exception\UtilityClassException;
use LeanMapper\Relationship\BelongsToMany;
use LeanMapper\Relationship\BelongsToOne;
use LeanMapper\Relationship\HasMany;
use LeanMapper\Relationship\HasOne;

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
	 * @param string $annotationType
	 * @param string $annotation
	 * @param EntityReflection $reflection
	 * @return Property
	 * @throws InvalidAnnotationException
	 */
	public static function createFromAnnotation($annotationType, $annotation, EntityReflection $reflection)
	{
		$namespace = $reflection->getNamespaceName();
		$aliases = $reflection->getAliases();

		$matches = array();
		$matched = preg_match('~
			^(null\|)?
			((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)
			(\[\])?
			(\|null)? \s+
			(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)
			(?:\s+m:(?:(hasOne|hasMany|belongsToOne|belongsToMany)(?:\\(([^)]+)\\))?))?
		~xi', $annotation, $matches);

		if (!$matched) {
			throw new InvalidAnnotationException("Invalid property annotation given: @$annotationType $annotation");
		}

		$propertyType = new PropertyType($matches[2], $namespace, $aliases);
		$containsCollection = $matches[3] !== '';

		$relationship = null;
		if (isset($matches[6])) {
			$relationship = self::createRelationship(
				$reflection->getName(),
				$propertyType,
				$containsCollection,
				$matches[6],
				isset($matches[7]) ? $matches[7] : null
			);
		}

		return new Property(
			substr($matches[5], 1),
			$propertyType,
				$annotationType === 'property',
				$matches[1] !== '' or $matches[3] !== '',
			$containsCollection,
			$relationship
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
	 * @return BelongsToMany|BelongsToOne|HasMany|HasOne|null
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
				return new HasOne($pieces[0] ? : $sourceTable, $pieces[1] ? : $targetTable . '_id', $pieces[2] ? : $targetTable);
			case 'hasMany':
				return new HasMany(
					$pieces[0] ? : $sourceTable,
					$pieces[1] ? : $sourceTable . '_id',
					$pieces[2] ? : $sourceTable . '_' . $targetTable,
					$pieces[3] ? : $targetTable . '_id',
					$pieces[4] ? : $targetTable
				);
			case 'belongsToOne':
				return new BelongsToOne($pieces[0] ? : $sourceTable, $pieces[1] ? : $sourceTable . '_id', $pieces[2] ? : $targetTable);
			case 'belongsToMany':
				return new BelongsToMany($pieces[0] ? : $sourceTable, $pieces[1] ? : $sourceTable . '_id', $pieces[2] ? : $targetTable);
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
