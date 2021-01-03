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

/**
 * @author Vojtěch Kohout
 */
class AliasesBuilder
{

    /** @var array<string, class-string> */
    private $aliases = [];

    /** @var class-string|string */
    private $current = '';

    /** @var string */
    private $lastPart = '';


    /**
     * Sets current definition to empty string
     */
    public function resetCurrent(): void
    {
        $this->current = $this->lastPart = '';
    }


    /**
     * Appends name to current definition
     */
    public function appendToCurrent(string $name): void
    {
        if ($this->current !== '') {
            $this->current .= '\\';
        }
        $this->current .= $this->lastPart = $name;
    }


    /**
     * Appends last part to current definition
     */
    public function setLast(string $name): void
    {
        $this->lastPart = $name;
    }


    /**
     * Finishes building of current definition and begins to build new one
     */
    public function finishCurrent(): void
    {
        /** @phpstan-ignore-next-line 'class-string' does not accept 'string' */
        $this->aliases[$this->lastPart] = $this->current;
        $this->resetCurrent();
    }


    /**
     * Creates new Aliases instance
     */
    public function getAliases(string $namespace = ''): Aliases
    {
        return new Aliases($this->aliases, $namespace);
    }

}
