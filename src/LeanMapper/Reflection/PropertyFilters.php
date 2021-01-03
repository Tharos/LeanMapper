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

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Helpers;

/**
 * Set of property filters
 *
 * @author Vojtěch Kohout
 */
class PropertyFilters
{

    /** @var array<array<string>> */
    private $filters = [];

    /** @var array<array<string, array<mixed>>> */
    private $targetedArgs = [];


    /**
     * @param array<array<string>> $filters
     * @param array<array<string, array<mixed>>> $targetedArgs
     */
    public function __construct(array $filters, array $targetedArgs)
    {
        $this->filters = $filters;
        $this->targetedArgs = $targetedArgs;
    }


    /**
     * Gets array of entity's filters (array of filter names)
     *
     * @return array<string>
     */
    public function getFilters(int $index = 0): array
    {
        if (!isset($this->filters[$index])) {
            return [];
        }
        return $this->filters[$index];
    }


    /**
     * Gets filters arguments hard-coded in annotation
     *
     * @return array<string, array<mixed>>
     */
    public function getFiltersTargetedArgs(int $index = 0): array
    {
        if (!isset($this->targetedArgs[$index])) {
            return [];
        }
        return $this->targetedArgs[$index];
    }


    /**
     * @throws InvalidAnnotationException
     */
    public static function createFromDefinition(string $definition): self
    {
        $propertyFilters = [];
        $propertyTargetedArgs = [];
        foreach (Helpers::split('#\s*\|\s*#', trim($definition)) as $set) {
            if ($set === '') {
                $propertyFilters[] = $propertyTargetedArgs[] = [];
                continue;
            }
            $filters = $targetedArgs = [];
            foreach (Helpers::split('#\s*,\s*#', $set) as $filter) {
                $matches = [];
                preg_match('~^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:#(.*))?$~', $filter, $matches);
                if (empty($matches)) {
                    throw new InvalidAnnotationException("Malformed filter name given: '$filter'.");
                }
                $filterName = $matches[1];
                if (isset($filters[$filterName])) {
                    unset($filters[$filterName], $targetedArgs[$filterName]);
                }
                $filters[$filterName] = $filterName;
                if (isset($matches[2])) {
                    $targetedArgs[$filterName] = [$matches[2]];
                }
            }
            $propertyFilters[] = $filters;
            $propertyTargetedArgs[] = $targetedArgs;
        }

        return new static($propertyFilters, $propertyTargetedArgs);
    }

}
