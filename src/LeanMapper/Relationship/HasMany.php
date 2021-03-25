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

namespace LeanMapper\Relationship;

/**
 * Has many relationship
 *
 * @author Vojtěch Kohout
 */
class HasMany
{

    /** @var string|null */
    private $columnReferencingSourceTable;

    /** @var string|null */
    private $relationshipTable;

    /** @var string|null */
    private $columnReferencingTargetTable;

    /** @var string|null */
    private $targetTable;

    /** @var string */
    private $strategy;


    public function __construct(?string $columnReferencingSourceTable, ?string $relationshipTable, ?string $columnReferencingTargetTable, ?string $targetTable, string $strategy)
    {
        $this->columnReferencingSourceTable = $columnReferencingSourceTable;
        $this->relationshipTable = $relationshipTable;
        $this->columnReferencingTargetTable = $columnReferencingTargetTable;
        $this->targetTable = $targetTable;
        $this->strategy = $strategy;
    }


    /**
     * Gets name of column referencing source table
     */
    public function getColumnReferencingSourceTable(): ?string
    {
        return $this->columnReferencingSourceTable;
    }


    /**
     * Gets name of relationship table
     */
    public function getRelationshipTable(): ?string
    {
        return $this->relationshipTable;
    }


    public function hasRelationshipTable(): bool
    {
        return $this->relationshipTable !== null;
    }


    /**
     * Gets name of column referencing target table
     */
    public function getColumnReferencingTargetTable(): ?string
    {
        return $this->columnReferencingTargetTable;
    }


    /**
     * Gets name of target table
     */
    public function getTargetTable(): ?string
    {
        return $this->targetTable;
    }


    /**
     * Gets strategy used to get referencing result
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

}
