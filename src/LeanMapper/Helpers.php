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

class Helpers
{

    public function __construct()
    {
        throw new Exception\UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
    }


    /**
     * @param  mixed $value
     */
    public static function getType($value): string
    {
        return is_object($value) ? ('instance of ' . get_class($value)) : gettype($value);
    }

}
