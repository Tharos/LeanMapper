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
use LeanMapper\Exception\InvalidMethodCallException;

/**
 * @author Vojtěch Kohout
 */
class FilteringResult
{

    /** @var Result */
    private $result;

    /** @var Closure */
    private $validationFunction;


    public function __construct(Result $result, ?Closure $validationFunction = null)
    {
        $this->result = $result;
        $this->validationFunction = $validationFunction;
    }


    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }


    /**
     * @throws InvalidMethodCallException
     */
    public function getValidationFunction(): Closure
    {
        if ($this->validationFunction === null) {
            throw new InvalidMethodCallException("FilteringResult doesn't have validation function.");
        }
        return $this->validationFunction;
    }


    public function hasValidationFunction(): bool
    {
        return $this->validationFunction !== null;
    }

}
