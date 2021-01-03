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

use LeanMapper\Exception\InvalidAnnotationException;
use LeanMapper\Helpers;

/**
 * Set of property access methods (given in useMethods flag)
 *
 * @author Vojtěch Kohout
 */
class PropertyMethods
{

    /** @var string */
    private $getter;

    /** @var string|null */
    private $setter;


    public function __construct(string $getter, ?string $setter)
    {
        $this->getter = $getter;
        $this->setter = $setter;
    }


    /**
     * Gets getter method
     */
    public function getGetter(): string
    {
        return $this->getter;
    }


    /**
     * Gets setter method
     */
    public function getSetter(): ?string
    {
        return $this->setter;
    }


    /**
     * @throws InvalidAnnotationException
     */
    public static function createFromDefinition(string $propertyName, bool $isWritable, string $definition): self
    {
        $ucName = ucfirst($propertyName);
        $getter = 'get' . $ucName;
        $setter = null;
        if ($isWritable) {
            $setter = 'set' . $ucName;
        }
        $counter = 0;
        foreach (Helpers::split('#\s*\|\s*#', trim($definition)) as $method) {
            $counter++;
            if ($counter > 2) {
                throw new InvalidAnnotationException('Property methods cannot have more than two parts.');
            }
            if ($method === '') {
                continue;
            }
            if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $method)) {
                throw new InvalidAnnotationException("Malformed access method name given: '$method'.");
            }
            if ($counter === 1) {
                $getter = $method;
            } else { // $counter === 2
                if (!$isWritable) {
                    throw new InvalidAnnotationException('Property methods can have one part only in read-only properties.');
                }
                $setter = $method;
            }
        }

        return new static($getter, $setter);
    }

}
