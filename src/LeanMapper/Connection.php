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

use Dibi\Row as DibiRow;
use LeanMapper\Exception\InvalidArgumentException;

/**
 * \Dibi\Connection with filter support
 *
 * @author Vojtěch Kohout
 * @method Fluent select(...$args)
 * @method Fluent update($table, iterable $args)
 * @method Fluent insert(string $table, iterable $args)
 * @method Fluent delete(string $table)
 */
class Connection extends \Dibi\Connection
{

    const WIRE_ENTITY = 1;

    const WIRE_PROPERTY = 2;

    const WIRE_ENTITY_AND_PROPERTY = 3;

    /** @var array<string, array{callable, string}> */
    private $filters;


    /**
     * Registers new filter
     *
     * @param string|int|null $wiringSchema
     * @throws InvalidArgumentException
     */
    public function registerFilter(string $name, callable $callback, $wiringSchema = null): void
    {
        if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $name)) {
            throw new InvalidArgumentException(
                "Invalid filter name given: '$name'. For filter names apply the same rules as for function names in PHP."
            );
        }
        if (isset($this->filters[$name])) {
            throw new InvalidArgumentException("Filter with name '$name' was already registered.");
        }
        if (!is_callable($callback, true)) {
            throw new InvalidArgumentException("Callback given for filter '$name' is not callable.");
        }
        $this->filters[$name] = [$callback, $this->translateWiringSchema($wiringSchema)];
    }


    public function hasFilter(string $name): bool
    {
        try {
            $this->checkFilterExistence($name);
        } catch (InvalidArgumentException $e) {
            return false;
        }
        return true;
    }


    /**
     * Gets callable filter's callback
     */
    public function getFilterCallback(string $name): callable
    {
        $this->checkFilterExistence($name);
        return $this->filters[$name][0];
    }


    /**
     * Gets wiring schema
     */
    public function getWiringSchema(string $filterName): string
    {
        $this->checkFilterExistence($filterName);
        return $this->filters[$filterName][1];
    }


    /**
     * Creates new instance of Fluent
     *
     * @return Fluent<int, DibiRow>
     */
    public function command(): \Dibi\Fluent
    {
        return new Fluent($this);
    }

    ////////////////////
    ////////////////////

    /**
     * @throws InvalidArgumentException
     */
    private function checkFilterExistence(string $name): void
    {
        if (!isset($this->filters[$name])) {
            throw new InvalidArgumentException("Filter with name '$name' was not found.");
        }
    }


    /**
     * @param string|int|null $wiringSchema
     * @throws InvalidArgumentException
     */
    private function translateWiringSchema($wiringSchema): string
    {
        if ($wiringSchema === null) {
            return '';
        }
        if (is_int($wiringSchema)) {
            $result = '';
            if ($wiringSchema & self::WIRE_ENTITY) {
                $result .= 'e';
            }
            if ($wiringSchema & self::WIRE_PROPERTY) {
                $result .= 'p';
            }
            $wiringSchema = $result;
        } elseif (!preg_match('#^(?:([pe])(?!.*\1))*$#', $wiringSchema)) {
            throw new InvalidArgumentException(
                "Invalid wiring schema given: '$wiringSchema'. Please use only characters p (Property) and e (Entity) in unique, non-repeating combination."
            );
        }
        return $wiringSchema;
    }

}
