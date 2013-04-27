<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper;

use DibiConnection;
use DibiRow;

/**
 * @author Vojtěch Kohout
 */
abstract class Repository
{

	/** @var DibiConnection */
	protected $connection;


	/**
	 * @param DibiConnection $connection
	 */
	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @param DibiRow $row
	 * @param string $entityClass
	 * @param string $table
	 * @return mixed
	 */
	protected function createEntity(DibiRow $row, $entityClass, $table)
	{
		$collection = new Result($row, $table, $this->connection);
		return new $entityClass($collection->getRow($row->id));
	}

	/**
	 * @param array $rows
	 * @param string $entityClass
	 * @param string $table
	 * @return array
	 */
	protected function createEntities(array $rows, $entityClass, $table)
	{
		$entities = array();
		$collection = new Result($rows, $table, $this->connection);
		foreach ($rows as $row) {
			$entities[$row->id] = new $entityClass($collection->getRow($row->id));
		}
		return $entities;
	}
	
}
