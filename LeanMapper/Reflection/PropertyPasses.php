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

	/** @var string[] */
	private $passes = array(null, null);


	/**
	 * @param string $definition
	 * @throws InvalidAnnotationException
	 */
	public function __construct($definition)
	{
		$passes = array();
		foreach (preg_split('#\s*\|\s*#', trim($definition)) as $pass) {
			if ($pass === '') {
				$passes[] = null;
				continue;
			}
			if (!preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#', $pass)) {
				throw new InvalidAnnotationException('Invalid method pass name given: ' . $pass);
			}
			$passes[] = $pass;
		}
		switch (count($passes)) {
			case 0:
				break;
			case 1:
				$this->passes = array($pass, $pass);
				break;
			case 2:
				$this->passes = $passes;
				break;
			default:
				throw new InvalidAnnotationException('PropertyPasses cannot have more than two parts.');
		}
		if (count($this->passes) > 2) {
			throw new InvalidAnnotationException('PropertyPasses cannot have more than two parts.');
		}
	}

	/**
	 * @return string|null
	 */
	public function getGetterPass()
	{
		return $this->passes[0];
	}

	/**
	 * @return string|null
	 */
	public function getSetterPass()
	{
		return $this->passes[1];
	}

}
