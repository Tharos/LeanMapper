<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

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
	 * Gets entity instance from given entity class name and argument
	 *
	 * @param string $entityClass
	 * @param Row|Traversable|array|null $arg
	 * @return Entity
	 */
	public function getEntity($entityClass, $arg = null);
	
}