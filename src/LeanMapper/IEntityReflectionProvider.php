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

use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use ReflectionMethod;

interface IEntityReflectionProvider
{

    /**
     * @return Property[]
     */
    function getProperties(EntityReflection $entityClass, ?IMapper $mapper = null): array;


    /**
     * @return ReflectionMethod[]
     */
    function getGetters(EntityReflection $entityClass): array;


    /**
     * @return ReflectionMethod[]
     */
    function getSetters(EntityReflection $entityClass): array;

}
