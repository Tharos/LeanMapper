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

use LeanMapper\Exception\InvalidArgumentException;

/**
 * Represents changes in M:N relationship
 *
 * @author Vojtěch Kohout
 */
class DataDifference
{

    /** @var array<array<string, mixed>> */
    private $added;

    /** @var array<array<string, mixed>> */
    private $removed;


    /**
     * @param array<array<string, mixed>> $added
     * @param array<array<string, mixed>> $removed
     */
    public function __construct(array $added, array $removed)
    {
        $this->added = $added;
        $this->removed = $removed;
    }


    /**
     * Performs quick lookup whether current instance may have any differences
     */
    public function mayHaveAny(): bool
    {
        return !empty($this->added) or !empty($this->removed);
    }


    /**
     * Gets differences by given pivot
     *
     * @param mixed $pivot
     * @return array<mixed, int>
     * @throws InvalidArgumentException
     */
    public function getByPivot($pivot): array
    {
        $result = [];
        foreach ($this->added as $entry) {
            if (!isset($entry[$pivot])) {
                throw new InvalidArgumentException("Invalid pivot given: '$pivot'.");
            }
            if (isset($result[$entry[$pivot]])) {
                $result[$entry[$pivot]]++;
            } else {
                $result[$entry[$pivot]] = 1;
            }
        }
        foreach ($this->removed as $entry) {
            if (!isset($entry[$pivot])) {
                throw new InvalidArgumentException("Invalid pivot given: '$pivot'.");
            }
            if (isset($result[$entry[$pivot]])) {
                $result[$entry[$pivot]]--;
                if ($result[$entry[$pivot]] === 0) {
                    unset($result[$entry[$pivot]]);
                }
            } else {
                $result[$entry[$pivot]] = -1;
            }
        }
        return $result;
    }

}
