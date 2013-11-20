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
 * Mapper interface
 *
 * @author Vojtěch Kohout
 */
interface IMapper
{

	/**
	 * Gets primary key name from given table name
	 *
	 * @param string $table
	 * @return string
	 */
	public function getPrimaryKey($table);

	/**
	 * Gets table name from given fully qualified entity class name
	 *
	 * @param string $entityClass
	 * @return string
	 */
	public function getTable($entityClass);

	/**
	 * Gets fully qualified entity class name from given table name
	 *
	 * @param string $table
	 * @param Row|null $row
	 * @return string
	 */
	public function getEntityClass($table, Row $row = null);

	/**
	 * Gets table column name from given fully qualified entity class name and entity field name
	 *
	 * @param string $entityClass
	 * @param string $field
	 * @return string
	 */
	public function getColumn($entityClass, $field);

	/**
	 * Gets entity field (property) name from given table name and table column
	 *
	 * @param string $table
	 * @param string $column
	 * @return string
	 */
	public function getEntityField($table, $column);

	/**
	 * Gets relationship table name from given source table name and target table name
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @return string
	 */
	public function getRelationshipTable($sourceTable, $targetTable);

	/**
	 * Gets name of column that contains foreign key from given source table name and target table name
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @return string
	 */
	public function getRelationshipColumn($sourceTable, $targetTable);

	/**
	 * Gets table name from repository class name
	 *
	 * @param string $repositoryClass
	 * @return string
	 */
	public function getTableByRepositoryClass($repositoryClass);

	/**
	 * Gets filters that should be used used every time when given entity is loaded from database
	 *
	 * @param string $entityClass
	 * @param Caller|null $caller
	 * @return array|ImplicitFilters
	 */
	public function getImplicitFilters($entityClass, Caller $caller = null);
	
}