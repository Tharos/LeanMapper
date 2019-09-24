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

/**
 * Set of property passes (given in passThru flag)
 *
 * @author Vojtěch Kohout
 */
class PropertyPasses
{

    /** @var string|null */
    private $getterPass;

    /** @var string|null */
    private $setterPass;



    /**
     * @param  string|null
     * @param  string|null
     */
    public function __construct($getterPass, $setterPass)
    {
        $this->getterPass = $getterPass;
        $this->setterPass = $setterPass;
    }



    /**
     * Gets getter pass
     *
     * @return string|null
     */
    public function getGetterPass()
    {
        return $this->getterPass;
    }



    /**
     * Gets setter pass
     *
     * @return string|null
     */
    public function getSetterPass()
    {
        return $this->setterPass;
    }


    /**
     * @param string $definition
     * @return static
     * @throws InvalidAnnotationException
     */
    public static function createFromDefinition($definition)
    {
        $counter = 0;
        $getterPass = null;
        $setterPass = null;
        foreach (preg_split('#\s*\|\s*#', trim($definition)) as $pass) {
            $counter++;
            if ($counter > 2) {
                throw new InvalidAnnotationException('Property passes cannot have more than two parts.');
            }
            if ($pass === '') {
                continue;
            }
            if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $pass)) {
                throw new InvalidAnnotationException("Malformed method pass name given: '$pass'.");
            }
            if ($counter === 1) {
                $getterPass = $pass;
            } else { // $counter === 2
                $setterPass = $pass;
            }
        }
        if ($counter === 1) {
            $setterPass = $getterPass;
        }

        return new static($getterPass, $setterPass);
    }

}
