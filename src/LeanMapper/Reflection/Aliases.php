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
 * Class names translator
 *
 * @author Vojtěch Kohout
 */
class Aliases
{

    /** @var array<string, class-string> */
    private $aliases;

    /** @var string */
    private $namespace;


    /**
     * @param array<string, class-string> $aliases
     */
    public function __construct(array $aliases, string $namespace = '')
    {
        $this->aliases = $aliases;
        $this->namespace = $namespace;
    }


    /**
     * Determines fully qualified class name
     *
     * @return string
     */
    public function translate(string $identifier): string
    {
        $pieces = explode('\\', $identifier);
        if (isset($this->aliases[$pieces[0]])) {
            $return = $this->aliases[$pieces[0]];
            if (count($pieces) > 1) {
                array_shift($pieces);
                $return .= '\\' . implode('\\', $pieces);
            }
        } else {
            $return = $this->namespace !== '' ? $this->namespace . '\\' . $identifier : $identifier;
        }
        return $return;
    }

}
