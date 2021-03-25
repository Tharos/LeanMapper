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

/**
 * \Dibi\Fluent with filter support
 *
 * @author Vojtěch Kohout
 * @method Connection getConnection()
 * @method Fluent select(...$field)
 * @method Fluent distinct()
 * @method Fluent from($table, ...$args = null)
 * @method Fluent where(...$cond)
 * @method Fluent groupBy(...$field)
 * @method Fluent having(...$cond)
 * @method Fluent orderBy(...$field)
 * @method Fluent limit(int $limit)
 * @method Fluent offset(int $offset)
 * @method Fluent join(...$table)
 * @method Fluent leftJoin(...$table)
 * @method Fluent innerJoin(...$table)
 * @method Fluent rightJoin(...$table)
 * @method Fluent outerJoin(...$table)
 * @method Fluent as(...$field)
 * @method Fluent on(...$cond)
 * @method Fluent and(...$cond)
 * @method Fluent or(...$cond)
 * @method Fluent using(...$cond)
 * @method Fluent asc()
 * @method Fluent desc()
 */
class Fluent extends \Dibi\Fluent
{

    /** @var array<int|string>|null */
    private $relatedKeys;


    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }


    /**
     * Applies given filter to current statement
     *
     * @param Closure|string $filter
     * @param mixed|null $args
     * @return FilteringResult|null
     */
    public function applyFilter($filter, $args = null)
    {
        $args = func_get_args();
        $args[0] = $this;
        return call_user_func_array(
            $filter instanceof Closure ? $filter : $this->getConnection()->getFilterCallback($filter),
            $args
        );
    }


    /**
     * @param array<mixed> $args
     */
    public function createSelect(?array $args = null): self
    {
        return call_user_func_array([$this->getConnection(), 'select'], func_get_args());
    }


    /**
     * Exports current state
     *
     * @param array<mixed> $args
     * @return array<mixed>
     */
    public function _export(?string $clause = null, array $args = []): array
    {
        return parent::_export($clause, $args);
    }


    /**
     * @return array<int|string>|null
     */
    public function getRelatedKeys(): ?array
    {
        return $this->relatedKeys;
    }


    /**
     * @param array<int|string>|null $keys
     * @throws InvalidArgumentException
     */
    public function setRelatedKeys(?array $keys): self
    {
        $this->relatedKeys = $keys;
        return $this;
    }

}
