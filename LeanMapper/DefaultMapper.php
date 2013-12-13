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

use LeanMapper\Exception\InvalidStateException;

/**
 * Default IMapper implementation
 *
 * @author Vojtěch Kohout
 */
class DefaultMapper implements IMapper
{

	/** @var string */
	protected $defaultEntityNamespace = 'Model\Entity';

	/** @var string */
	protected $relationshipTableGlue = '_';


	/*
	 * @inheritdoc
	 */
	public function getPrimaryKey($table)
	{
		return 'id';
	}

	/*
	 * @inheritdoc
	 */
	public function getTable($entityClass)
	{
		return strtolower($this->trimNamespace($entityClass));
	}

	/*
	 * @inheritdoc
	 */
	public function getEntityClass($table, Row $row = null)
	{
		return ($this->defaultEntityNamespace !== null ? $this->defaultEntityNamespace . '\\' : '') . ucfirst($table);
	}

	/*
	 * @inheritdoc
	 */
	public function getColumn($entityClass, $field)
	{
		return $field;
	}

	/*
	 * @inheritdoc
	 */
	public function getEntityField($table, $column)
	{
		return $column;
	}

	/*
	 * @inheritdoc
	 */
	public function getRelationshipTable($sourceTable, $targetTable)
	{
		return $sourceTable . $this->relationshipTableGlue . $targetTable;
	}

	/*
	 * @inheritdoc
	 */
	public function getRelationshipColumn($sourceTable, $targetTable)
	{
		return $targetTable . '_' . $this->getPrimaryKey($targetTable);
	}

	/*
	 * @inheritdoc
	 */
	public function getTableByRepositoryClass($repositoryClass)
	{
		$matches = array();
		if (preg_match('#([a-z0-9]+)repository$#i', $repositoryClass, $matches)) {
			return strtolower($matches[1]);
		}
		throw new InvalidStateException('Cannot determine table name.');
	}

	/*
	 * @inheritdoc
	 */
	public function getImplicitFilters($entityClass, Caller $caller = null)
	{
		return array();
	}

	/**
	 * Trims namespace part from fully qualified class name
	 *
	 * @param $class
	 * @return string
	 */
	protected function trimNamespace($class)
	{
		$class = explode('\\', $class);
		return end($class);
	}

}
