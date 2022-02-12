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
 * @implements \Iterator<string, mixed>
 */
class ResultProxy implements \Iterator
{

    /** @var Result */
    private $result;


    public function __construct(Result $result)
    {
        $this->result = $result;
    }


    /**
     * @return array<string, mixed>
     */
    public function getData(int $id): array
    {
        return $this->result->getData($id);
    }


    public function setReferencedResult(Result $referencedResult, string $table, ?string $viaColumn = null): void
    {
        $this->result->setReferencedResult($referencedResult, $table, $viaColumn);
    }


    public function setReferencingResult(Result $referencingResult, string $table, ?string $viaColumn = null, ?string $strategy = Result::STRATEGY_IN): void
    {
        $this->result->setReferencingResult($referencingResult, $table, $viaColumn, $strategy);
    }

    //========== interface \Iterator ====================

    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->result->current();
    }


    public function next(): void
    {
        $this->result->next();
    }


    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->result->key();
    }


    public function valid(): bool
    {
        return $this->result->valid();
    }


    public function rewind(): void
    {
        $this->result->rewind();
    }

}
