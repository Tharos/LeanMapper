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
 * Base class for belongs to relationships
 *
 * @author Vojtěch Kohout
 */
abstract class BelongsTo
{

    /** @var string|null */
    private $columnReferencingSourceTable;

    /** @var string|null */
    private $targetTable;

    /** @var string */
    private $strategy;


    public function __construct(?string $columnReferencingSourceTable, ?string $targetTable, string $strategy)
    {
        $this->columnReferencingSourceTable = $columnReferencingSourceTable;
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
     * Gets name of target table
     */
    public function getTargetTable(): ?string
    {
        return $this->targetTable;
    }


    public function hasTargetTable(): bool
    {
        return $this->targetTable !== null;
    }


    /**
     * Gets strategy used to get referencing result
     */
    public function getStrategy(): string
    {
        return $this->strategy;
    }

}
