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
 * Has one relationship
 *
 * @author Vojtěch Kohout
 */
class HasOne
{

    /** @var string|null */
    private $columnReferencingTargetTable;

    /** @var string|null */
    private $targetTable;


    public function __construct(?string $columnReferencingTargetTable, ?string $targetTable)
    {
        $this->columnReferencingTargetTable = $columnReferencingTargetTable;
        $this->targetTable = $targetTable;
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


    public function hasTargetTable(): bool
    {
        return $this->targetTable !== null;
    }

}
