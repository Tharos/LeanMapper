<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
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
	 * @param string $annotation
	 * @param EntityReflection $reflection
	 * @return Property
	 * @throws InvalidAnnotationException
	 */
	public static function createFromAnnotation($annotation, EntityReflection $reflection)
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
			throw new InvalidAnnotationException("Invalid property annotation given: @property $annotation");
		}
		$containsCollection = $matches[3] !== '';
		$isNullable = ($matches[1] !== '' or $matches[4] !== '');

		if ($containsCollection and $isNullable) {
			throw new InvalidAnnotationException("It doesn't make sense to have a property containing collection nullable: @property $annotation");
		}
		$propertyType = new PropertyType($matches[2], $namespace, $aliases);

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
			$isNullable,
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
				return new HasOne($pieces[0] ? : $targetTable . '_id', $pieces[1] ? : $targetTable);
			case 'hasMany':
				return new HasMany(
					$pieces[0] ? : $sourceTable . '_id',
					$pieces[1] ? : $sourceTable . '_' . $targetTable,
					$pieces[2] ? : $targetTable . '_id',
					$pieces[3] ? : $targetTable
				);
			case 'belongsToOne':
				return new BelongsToOne($pieces[0] ? : $sourceTable . '_id', $pieces[1] ? : $targetTable);
			case 'belongsToMany':
				return new BelongsToMany($pieces[0] ? : $sourceTable . '_id', $pieces[1] ? : $targetTable);
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
