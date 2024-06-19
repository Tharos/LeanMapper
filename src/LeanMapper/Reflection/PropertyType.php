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
 * Property type
 *
 * @author Vojtěch Kohout
 */
class PropertyType
{

    /** @var string */
    private $type;

    /** @var bool */
    private $isBasicType;


    public function __construct(string $type, Aliases $aliases)
    {
        if (preg_match('#^(boolean|bool|integer|int|float|string|array)$#', $type)) {
            if ($type === 'bool') {
                $type = 'boolean';
            }
            if ($type === 'int') {
                $type = 'integer';
            }
            $this->isBasicType = true;

        } elseif ($type === 'non-empty-string') {
            $this->isBasicType = true;

        } else {
            if (substr($type, 0, 1) === '\\') {
                $type = substr($type, 1);
            } else {
                $type = $aliases->translate($type);
            }
            $this->isBasicType = false;
        }
        $this->type = $type;
    }


    /**
     * Gets type
     */
    public function getType(): string
    {
        return $this->type;
    }


    /**
     * Tells whether current type is basic type (boolean|integer|float|string|array)
     */
    public function isBasicType(): bool
    {
        return $this->isBasicType;
    }

}
