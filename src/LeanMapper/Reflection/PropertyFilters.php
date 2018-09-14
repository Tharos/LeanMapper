<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;

/**
 * Set of property filters
 *
 * @author Vojtěch Kohout
 */
class PropertyFilters
{

    /** @var array */
    protected $filters = [];

    /** @var array */
    protected $targetedArgs = [];



    /**
     * @param string $definition
     * @throws InvalidAnnotationException
     */
    public function __construct($definition)
    {
        foreach (preg_split('#\s*\|\s*#', trim($definition)) as $set) {
            if ($set === '') {
                $this->filters[] = $this->targetedArgs[] = [];
                continue;
            }
            $filters = $targetedArgs = [];
            foreach (preg_split('#\s*,\s*#', $set) as $filter) {
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
            $this->filters[] = $filters;
            $this->targetedArgs[] = $targetedArgs;
        }
    }



    /**
     * Gets array of entity's filters (array of filter names)
     *
     * @param int $index
     * @return array
     */
    public function getFilters($index = 0)
    {
        if (!isset($this->filters[$index])) {
            return [];
        }
        return $this->filters[$index];
    }



    /**
     * Gets filters arguments hard-coded in annotation
     *
     * @param int $index
     * @return array
     */
    public function getFiltersTargetedArgs($index = 0)
    {
        if (!isset($this->targetedArgs[$index])) {
            return [];
        }
        return $this->targetedArgs[$index];
    }

}
