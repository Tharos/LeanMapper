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

use LeanMapper\Exception\InvalidStateException;

/**
 * Default IMapper implementation
 *
 * @author Vojtěch Kohout
 */
class DefaultMapper implements IMapper
{

    /** @var string|null */
    protected $defaultEntityNamespace;

    /** @var string */
    protected $relationshipTableGlue = '_';


    public function __construct(?string $defaultEntityNamespace = 'Model\Entity')
    {
        $this->defaultEntityNamespace = $defaultEntityNamespace;
    }


    public function getPrimaryKey(string $table): string
    {
        return 'id';
    }


    public function getTable(string $entityClass): string
    {
        return strtolower(Helpers::trimNamespace($entityClass));
    }


    public function getEntityClass(string $table, ?Row $row = null): string
    {
        return ($this->defaultEntityNamespace !== null ? $this->defaultEntityNamespace . '\\' : '') . ucfirst($table);
    }


    public function getColumn(string $entityClass, string $field): string
    {
        return $field;
    }


    public function getEntityField(string $table, string $column): string
    {
        return $column;
    }


    public function getRelationshipTable(string $sourceTable, string $targetTable): string
    {
        return $sourceTable . $this->relationshipTableGlue . $targetTable;
    }


    public function getRelationshipColumn(string $sourceTable, string $targetTable, ?string $relationshipName = null): string
    {
        return ($relationshipName !== null ? $relationshipName : $targetTable) . '_' . $this->getPrimaryKey($targetTable);
    }


    public function getTableByRepositoryClass(string $repositoryClass): string
    {
        $matches = [];
        if (preg_match('#([a-z0-9]+)repository$#i', $repositoryClass, $matches)) {
            return strtolower($matches[1]);
        }
        throw new InvalidStateException('Cannot determine table name.');
    }


    public function getImplicitFilters(string $entityClass, ?Caller $caller = null)
    {
        return [];
    }


    public function convertToRowData(string $table, array $values): array
    {
        return $values;
    }


    public function convertFromRowData(string $table, array $data): array
    {
        return $data;
    }


    protected function trimNamespace(string $class): string
    {
        return Helpers::trimNamespace($class);
    }

}
