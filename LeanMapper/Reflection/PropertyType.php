<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper\Reflection;

/**
 * @author Vojtěch Kohout
 */
class PropertyType
{

	/** @var string */
	private $type;

	/** @var bool */
	private $isBasicType;


	/**
	 * @param string $type
	 * @param Aliases $aliases
	 */
	public function __construct($type, Aliases $aliases)
	{
		if (preg_match('#^(boolean|bool|integer|int|float|string|array)$#', $type)) {
			if ($type === 'bool') {
				$type = 'boolean';
			}
			if ($type === 'int') {
				$type = 'integer';
			}
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
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isBasicType()
	{
		return $this->isBasicType;
	}

}
