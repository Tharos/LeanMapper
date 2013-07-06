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
 * Interface for custom mappings
 *
 * @author Vojtěch Kohout
 */
interface IMapper
{

	/**
	 * Returns primary key name from given table name
	 *
	 * @param string $table
	 * @return string
	 */
	public function getPrimaryKey($table);

	/**
	 * Returns table name from given fully qualified entity class name
	 *
	 * @param string $entityClass
	 * @return string
	 */
	public function getTable($entityClass);

	/**
	 * Returns fully qualified entity class name from given table name
	 *
	 * @param string $table
	 * @param Row|null $row
	 * @return string
	 */
	public function getEntityClass($table, Row $row = null);

	/**
	 * Returns table column name from given fully qualified entity class name and entity field name
	 *
	 * @param string $entityClass
	 * @param string $field
	 * @return string
	 */
	public function getColumn($entityClass, $field);

	/**
	 * Returns entity field name from given table name and table column
	 *
	 * @param string $table
	 * @param string $column
	 * @return string
	 */
	public function getEntityField($table, $column);

	/**
	 * Returns relationship table name from given source table name and target table name
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @return string
	 */
	public function getRelationshipTable($sourceTable, $targetTable);

	/**
	 * Returns name of column that contains foreign key from given source table name and target table name
	 *
	 * @param string $sourceTable
	 * @param string $targetTable
	 * @return string
	 */
	public function getRelationshipColumn($sourceTable, $targetTable);

	/**
	 * Returns table name from repository class name
	 *
	 * @param string $repositoryClass
	 * @return string
	 */
	public function getTableByRepositoryClass($repositoryClass);
	
}