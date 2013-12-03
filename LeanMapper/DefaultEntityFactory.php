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

/**
 * Default IEntityFactory implementation
 *
 * @author Vojtěch Kohout
 */
class DefaultEntityFactory implements IEntityFactory
{

	/*
	 * @inheritdoc
	 */
	public function createEntity($entityClass, $arg = null)
	{
		return new $entityClass($arg);
	}

	/*
	 * @inheritdoc
	 */
	public function createCollection(array $entities)
	{
		return $entities;
	}

}
