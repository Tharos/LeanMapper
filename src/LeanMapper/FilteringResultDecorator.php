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

/**
 * @author Vojtěch Kohout
 */
class FilteringResultDecorator
{

    /** @var FilteringResult */
    private $filteringResult;

    /** @var array<mixed> */
    private $baseArgs;


    /**
     * @param  array<mixed> $baseArgs
     */
    public function __construct(FilteringResult $filteringResult, array $baseArgs)
    {
        $this->filteringResult = $filteringResult;
        $this->baseArgs = $baseArgs;
    }


    public function getResult(): Result
    {
        return $this->filteringResult->getResult();
    }


    /**
     * @param  array<int|string> $relatedKeys
     * @param  array<mixed> $args
     */
    public function isValidFor(array $relatedKeys, array $args): bool
    {
        if (!$this->filteringResult->hasValidationFunction()) {
            return true;
        }
        return call_user_func_array(
            $this->filteringResult->getValidationFunction(),
            array_merge([$relatedKeys], $this->baseArgs, $args)
        );
    }

}
