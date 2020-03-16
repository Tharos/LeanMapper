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

use Closure;
use LeanMapper\Exception\InvalidArgumentException;
use LeanMapper\Reflection\Property;

/**
 * Encapsulation of filter call
 *
 * @author Vojtěch Kohout
 */
class Filtering
{

    /** @var array */
    private $filters;

    /** @var array */
    private $args;

    /** @var Entity|null */
    private $entity;

    /** @var Property|null */
    private $property;

    /** @var array */
    private $targetedArgs;



    /**
     * @param array|string|Closure $filters
     * @throws InvalidArgumentException
     */
    public function __construct($filters, ?array $args = null, ?Entity $entity = null, ?Property $property = null, array $targetedArgs = [])
    {
        if (!is_array($filters)) {
            if (!is_string($filters) and !($filters instanceof Closure)) {
                throw new InvalidArgumentException(
                    "Argument \$filters must contain either string (name of filter), instance of Closure or array (with names of filters or instances of Closure)."
                );
            }
            $filters = [$filters];
        }
        $this->filters = $filters;
        $this->args = $args !== null ? $args : [];
        $this->entity = $entity;
        $this->property = $property;
        $this->targetedArgs = $targetedArgs;
    }



    public function getFilters(): array
    {
        return $this->filters;
    }



    public function getArgs(): array
    {
        return $this->args;
    }



    public function getEntity(): ?Entity
    {
        return $this->entity;
    }



    public function getProperty(): ?Property
    {
        return $this->property;
    }



    public function getTargetedArgs(): array
    {
        return $this->targetedArgs;
    }

}
