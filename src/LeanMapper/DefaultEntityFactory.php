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

/**
 * Default IEntityFactory implementation
 *
 * @author Vojtěch Kohout
 */
class DefaultEntityFactory implements IEntityFactory
{

    public function createEntity(string $entityClass, $arg = null): Entity
    {
        $entity = new $entityClass($arg);
        assert($entity instanceof Entity);
        return $entity;
    }


    public function createCollection(array $entities)
    {
        return $entities;
    }

}
