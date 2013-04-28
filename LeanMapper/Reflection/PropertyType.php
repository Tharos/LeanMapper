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
	 * @param string $namespace
	 * @param array $aliases
	 */
	public function __construct($type, $namespace, array $aliases)
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
				if (isset($aliases[$type])) {
					$type = $aliases[$type];
				} else {
					$type = $namespace !== '' ? $namespace . '\\' . $type : $type;
				}
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
