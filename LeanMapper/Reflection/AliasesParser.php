<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\UtilityClassException;

/**
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
	 * @param string $source
	 * @param string $namespace
	 * @return array
	 */
	public static function parseSource($source, $namespace = '')
	{
		$builder = new AliasesBuilder;

		$states = array(
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
			self::STATE_JUST_FINISHED => function ($token) use ($builder) {
				if ($token === ';') {
					return AliasesParser::STATE_WAITING_FOR_USE;
				}
				return AliasesParser::STATE_GATHERING;
			}
		);

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