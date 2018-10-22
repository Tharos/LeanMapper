<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

namespace LeanMapper;

use LeanMapper\Reflection\AnnotationsParser;
use LeanMapper\Reflection\Property;
use LeanMapper\Reflection\PropertyFactory;
use ReflectionClass;
use ReflectionMethod;

class DefaultEntityReflectionProvider implements IEntityReflectionProvider
{

    /** @var array */
    protected $internalGetters = ['getData', 'getRowData', 'getModifiedRowData', 'getCurrentReflection', 'getReflection', 'getHasManyRowDifferences', 'getEntityClass'];



    /**
     * @return Property[]
     */
    public function getProperties(ReflectionClass $entityClass, IMapper $mapper = null)
    {
        $properties = [];
        $annotationTypes = ['property', 'property-read'];
        foreach ($this->getFamilyLine($entityClass) as $member) {
            foreach ($annotationTypes as $annotationType) {
                foreach (AnnotationsParser::parseMultiLineAnnotationValues($annotationType, $member->getDocComment()) as $definition) {
                    $properties[] = PropertyFactory::createFromAnnotation($annotationType, $definition, $member, $mapper);
                }
            }
        }
        return $properties;
    }



    /**
     * @return ReflectionMethod[]
     */
    public function getGetters(ReflectionClass $entityClass)
    {
        $getters = [];
        foreach ($entityClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (strlen($name) > 3 && substr($name, 0, 3) === 'get') {
                $getters[$name] = $method;
            }
        }
        return array_diff_key($getters, array_flip($this->internalGetters));
    }



    /**
     * @return ReflectionMethod[]
     */
    public function getSetters(ReflectionClass $entityClass)
    {
        $setters = [];
        foreach ($entityClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (strlen($name) > 3 && substr($name, 0, 3) === 'set') {
                $setters[$name] = $method;
            }
        }
        return $setters;
    }



    /**
     * @return ReflectionClass[]
     */
    protected function getFamilyLine(ReflectionClass $member)
    {
        $line = [$member];
        while ($member = $member->getParentClass()) {
            if ($member->name === 'LeanMapper\Entity') {
                break;
            }
            $line[] = $member;
        }
        return array_reverse($line);
    }

}
