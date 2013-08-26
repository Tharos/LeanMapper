<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper\Reflection;

use LeanMapper\Exception\InvalidAnnotationException;

/**
 * Set of property passes (given in passThru flag)
 *
 * @author Vojtěch Kohout
 */
class PropertyPasses
{

	/** @var string */
	private $getterPass;

	/** @var string */
	private $setterPass;


	/**
	 * @param string $definition
	 * @throws InvalidAnnotationException
	 */
	public function __construct($definition)
	{
		$counter = 0;
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
				$this->getterPass = $pass;
			} else { // $counter === 2
				$this->setterPass = $pass;
			}
		}
		if ($counter === 1) {
			$this->setterPass = $this->getterPass;
		}
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

}
