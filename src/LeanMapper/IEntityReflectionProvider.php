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

use LeanMapper\Reflection\Property;
use ReflectionClass;
use ReflectionMethod;

interface IEntityReflectionProvider
{

    /**
     * @return Property[]
     */
    function getProperties(ReflectionClass $entityClass, IMapper $mapper = null);



    /**
     * @return ReflectionMethod[]
     */
    function getGetters(ReflectionClass $entityClass);



    /**
     * @return ReflectionMethod[]
     */
    function getSetters(ReflectionClass $entityClass);

}
