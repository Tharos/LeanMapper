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

use Traversable;

/**
 * Entity factory
 *
 * @author Vojtěch Kohout
 */
interface IEntityFactory
{

    /**
     * Creates entity instance from given entity class name and argument
     *
     * @param Row|iterable<string, mixed>|null $arg
     */
    function createEntity(string $entityClass, $arg = null): Entity;


    /**
     * Allows wrap set of entities in custom collection
     *
     * @param Entity[] $entities
     * @return iterable<Entity>
     */
    function createCollection(array $entities);

}
