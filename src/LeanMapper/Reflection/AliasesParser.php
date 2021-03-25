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

use LeanMapper\Exception\UtilityClassException;

/**
 * Use statement parser (state machine)
 *
 * @author Vojtěch Kohout
 */
class AliasesParser
{

    const STATE_WAITING_FOR_USE = 1;

    const STATE_GATHERING = 2;

    const STATE_IN_AS_PART = 3;

    const STATE_JUST_FINISHED = 4;


    /**
     * @throws UtilityClassException
     */
    public function __construct()
    {
        throw new UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
    }


    /**
     * Creates Aliases instance relevant to given class source code
     *
     * @param string $source
     * @param string $namespace
     * @return Aliases
     */
    public static function parseSource(string $source, string $namespace = ''): Aliases
    {
        $matches = [];
        preg_match_all('#use[^;()]+?;#im', $source, $matches);
        $source = '<?php ' . implode('', $matches[0]);

        $builder = new AliasesBuilder;

        $states = [
            self::STATE_WAITING_FOR_USE => function ($token) use ($builder) {
                if (is_array($token) and $token[0] === T_USE) {
                    $builder->resetCurrent();
                    return AliasesParser::STATE_GATHERING;
                }
                return AliasesParser::STATE_WAITING_FOR_USE;
            },
            self::STATE_GATHERING => function ($token) use ($builder) {
                if (is_array($token)) {
                    if ($token[0] === T_STRING) {
                        $builder->appendToCurrent($token[1]);
                    } elseif (PHP_VERSION_ID >= 80000 && $token[0] === T_NAME_QUALIFIED) {
                        $builder->appendToCurrent($token[1]);
                        $builder->setLast(substr($token[1], strrpos($token[1], '\\') + 1));
                        return AliasesParser::STATE_GATHERING;
                    } elseif ($token[0] === T_AS) {
                        return AliasesParser::STATE_IN_AS_PART;
                    }
                } else {
                    if ($token === ';') {
                        $builder->finishCurrent();
                        return AliasesParser::STATE_WAITING_FOR_USE;
                    } elseif ($token === ',') {
                        $builder->finishCurrent();
                    }
                }
                return AliasesParser::STATE_GATHERING;
            },
            self::STATE_IN_AS_PART => function ($token) use ($builder) {
                if (is_array($token)) {
                    if ($token[0] === T_STRING) {
                        $builder->setLast($token[1]);
                        $builder->finishCurrent();
                        return AliasesParser::STATE_JUST_FINISHED;
                    }
                }
                return AliasesParser::STATE_IN_AS_PART;
            },
            self::STATE_JUST_FINISHED => function ($token) {
                if ($token === ';') {
                    return AliasesParser::STATE_WAITING_FOR_USE;
                }
                return AliasesParser::STATE_GATHERING;
            },
        ];

        $state = $states[self::STATE_WAITING_FOR_USE];
        foreach (token_get_all($source) as $token) {
            $state = $states[$state($token)];
            if (is_array($token)) {
                $token[3] = token_name($token[0]);
            }
        }

        return $builder->getAliases($namespace);
    }

}
