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

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Exception\UtilityClassException;
use LeanMapper\Helpers;
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
     * @throws InvalidAnnotationException
     */
    public static function createFromAnnotation(string $annotationType, string $annotation, EntityReflection $entityReflection, ?IMapper $mapper = null, ?callable $factory = null): Property
    {
        $aliases = $entityReflection->getAliases();

        $matches = [];
        $matched = preg_match(
            '~
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
            (?:\s*\(([^)]+)\))?
            (?:\s+([\s\S]*)\s*)?
        ~xi',
            $annotation,
            $matches
        );

        if (!$matched) {
            throw new InvalidAnnotationException(
                "Invalid property definition given: @$annotationType $annotation in entity {$entityReflection->getName()}."
            );
        }

        $propertyType = new PropertyType($matches[2], $aliases);
        $isWritable = $annotationType === 'property';
        $containsCollection = $matches[3] !== '';
        if ($propertyType->isBasicType() and $containsCollection) {
            throw new InvalidAnnotationException(
                "Invalid property type definition given: @$annotationType $annotation in entity {$entityReflection->getName()}. Lean Mapper doesn't support <type>[] notation for basic types."
            );
        }
        $isNullable = ($matches[1] !== '' or $matches[4] !== '');
        $name = (string) substr($matches[5], 1);

        $hasDefaultValue = false;
        $defaultValue = null;
        if (isset($matches[6]) and $matches[6] !== '') {
            $defaultValue = str_replace('\"', '"', $matches[6]);
        } elseif (isset($matches[7]) and $matches[7] !== '') {
            $defaultValue = str_replace("\\'", "'", $matches[7]);
        } elseif (isset($matches[8]) and $matches[8] !== '') {
            $defaultValue = $matches[8];
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
        $customFlags = [];
        $customColumn = null;

        if (isset($matches[10])) {
            preg_match_all('~m:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff-]*)\s*(\(((?>[^)(]+|(?2))*)\))?~', $matches[10], $flagMatches, PREG_SET_ORDER);

            foreach ($flagMatches as $match) {
                if ($match[1] === 'belongsToOne' || $match[1] === 'belongsToMany') {
                    $isWritable = false;
                    break;
                }
            }

            foreach ($flagMatches as $match) {
                $flag = (string) $match[1];
                $flagArgument = (isset($match[3]) and $match[3] !== '') ? $match[3] : null;

                switch ($flag) {
                    case 'hasOne':
                    case 'hasMany':
                    case 'belongsToOne':
                    case 'belongsToMany':
                        if ($relationship !== null) {
                            throw new InvalidAnnotationException(
                                "It doesn't make sense to have multiple relationship definitions in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $relationship = self::createRelationship(
                            $entityReflection->getName(),
                            $name,
                            $propertyType,
                            $flag,
                            $flagArgument,
                            $mapper
                        );

                        $column = null;
                        if ($relationship instanceof Relationship\HasOne) {
                            $column = $relationship->getColumnReferencingTargetTable();
                        }
                        break;
                    case 'useMethods':
                        if ($propertyMethods !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple m:useMethods flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $propertyMethods = PropertyMethods::createFromDefinition($name, $isWritable, $flagArgument);
                        break;
                    case 'filter':
                        if ($propertyFilters !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple m:filter flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $propertyFilters = PropertyFilters::createFromDefinition($flagArgument);
                        break;
                    case 'passThru':
                        if ($propertyPasses !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple m:passThru flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $propertyPasses = PropertyPasses::createFromDefinition($flagArgument);
                        break;
                    case 'enum':
                        if ($propertyValuesEnum !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple values enumerations found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        if ($flagArgument === null) {
                            throw new InvalidAnnotationException(
                                "Parameter of m:enum flag was not found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $propertyValuesEnum = PropertyValuesEnum::createFromDefinition($flagArgument, $entityReflection);
                        break;
                    case 'column':
                        if ($customColumn !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple column name settings found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        if ($flagArgument === null) {
                            throw new InvalidAnnotationException(
                                "Parameter of m:column flag was not found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $customColumn = $flagArgument;
                        break;
                    case 'default':
                        if ($defaultValue !== null) {
                            throw new InvalidAnnotationException(
                                "Multiple default value settings found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        if ($flagArgument === null) {
                            throw new InvalidAnnotationException(
                                "Parameter of m:default flag was not found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $matched = preg_match(
                            '~
                            ^\s*(?:
                                "((?:\\\\"|[^"])+)" |      # double quoted string
                                \'((?:\\\\\'|[^\'])+)\' |  # single quoted string
                                ([^ ]*)                    # unquoted value
                            )
                        ~xi',
                            $flagArgument,
                            $matches
                        );
                        if (!$matched) {
                            throw new \Exception;
                        }
                        if (isset($matches[1]) and $matches[1] !== '') {
                            $defaultValue = str_replace('\"', '"', $matches[1]);
                        } elseif (isset($matches[2]) and $matches[2] !== '') {
                            $defaultValue = str_replace("\\'", "'", $matches[2]);
                        } elseif (isset($matches[3]) and $matches[3] !== '') {
                            $defaultValue = $matches[3];
                        }

                        break;
                    default:
                        if (array_key_exists($flag, $customFlags)) {
                            throw new InvalidAnnotationException(
                                "Multiple m:$flag flags found in property definition: @$annotationType $annotation in entity {$entityReflection->getName()}."
                            );
                        }
                        $customFlags[$flag] = $flagArgument;
                }
            }
        }

        if ($defaultValue !== null) {
            $hasDefaultValue = true;

            try {
                $defaultValue = self::fixDefaultValue($defaultValue, $propertyType, $isNullable);
            } catch (InvalidAnnotationException $e) {
                throw new InvalidAnnotationException(
                    "Invalid property definition given: @$annotationType $annotation in entity {$entityReflection->getName()}, " . lcfirst(
                        $e->getMessage()
                    )
                );
            }
        }

        $column = $customColumn ?: $column;

        if ($factory !== null) {
            return call_user_func(
                $factory,
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
     * @return mixed
     * @throws InvalidAnnotationException
     */
    private static function createRelationship(
        string $sourceClass,
        string $propertyName,
        PropertyType $propertyType,
        string $relationshipType,
        ?string $definition = null,
        ?IMapper $mapper = null
    ) {
        $flags = null;
        $strategy = null;
        if ($relationshipType !== 'hasOne') {
            $strategy = Result::STRATEGY_IN; // default strategy
            if ($definition !== null) {
                list($definition, $flags) = self::parseRelationshipFlags($definition);

                if (isset($flags['union'])) {
                    $strategy = Result::STRATEGY_UNION;
                }

                if (isset($flags['inversed']) && $relationshipType !== 'hasMany') {
                    throw new InvalidAnnotationException(
                        "It doesn't make sense to have #inversed in $relationshipType relationship in entity $sourceClass."
                    );
                }
            }
        }
        $pieces = array_replace(array_fill(0, 6, ''), $definition !== null ? explode(':', $definition) : []);

        $sourceTable = ($mapper !== null ? $mapper->getTable($sourceClass) : null);
        $targetTable = ($mapper !== null ? $mapper->getTable($propertyType->getType()) : null);

        switch ($relationshipType) {
            case 'hasOne':
                $relationshipColumn = ($mapper !== null ? $mapper->getRelationshipColumn(
                    $sourceTable,
                    $targetTable,
                    $propertyName
                ) : self::getSurrogateRelationshipColumn($propertyType));
                return new Relationship\HasOne($pieces[0] ?: $relationshipColumn, $pieces[1] ?: $targetTable);
            case 'hasMany':
                $relationshipTable = null;

                if ($pieces[1]) {
                    if (isset($flags['inversed'])) {
                        throw new InvalidAnnotationException(
                            "It doesn't make sense to combine #inversed and hardcoded relationship table in entity $sourceClass."
                        );
                    }
                    $relationshipTable = $pieces[1];
                } elseif ($mapper !== null) {
                    if (isset($flags['inversed'])) {
                        $relationshipTable = $mapper->getRelationshipTable($targetTable, $sourceTable);
                    } else {
                        $relationshipTable = $mapper->getRelationshipTable($sourceTable, $targetTable);
                    }
                }

                return new Relationship\HasMany(
                    $pieces[0] ?: ($mapper !== null ? $mapper->getRelationshipColumn(
                        $mapper->getRelationshipTable($sourceTable, $targetTable),
                        $sourceTable
                    ) : null),
                    $relationshipTable,
                    $pieces[2] ?: ($mapper !== null ? $mapper->getRelationshipColumn(
                        $mapper->getRelationshipTable($sourceTable, $targetTable),
                        $targetTable
                    ) : null),
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
     * @return array{string, array<string, true>}  (definition, flags)
     */
    private static function parseRelationshipFlags(string $definition): array
    {
        $flags = [];

        while (($pos = strrpos($definition, '#')) !== false) {
            $flag = substr($definition, $pos + 1);

            if ($flag === 'union' || $flag === 'inversed') {
                $flags[$flag] = true;
                $definition = substr($definition, 0, $pos);
            } else {
                break;
            }
        }

        return [$definition, $flags];
    }


    private static function getSurrogateRelationshipColumn(PropertyType $propertyType): string
    {
        return strtolower(Helpers::trimNamespace($propertyType->getType())) . '{hasOne:' . self::generateRandomString(10) . '}';
    }


    private static function generateRandomString(int $length): string
    {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }


    /**
     * @param mixed $value
     * @return mixed
     * @throws InvalidAnnotationException
     */
    private static function fixDefaultValue($value, PropertyType $propertyType, bool $isNullable)
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
                if (!is_numeric($value) && !preg_match('~0x[0-9a-f]+~', $value)) {
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
                return [];
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
