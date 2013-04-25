<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * @license MIT
 * @link http://leanmapper.tharos.cz
 */

namespace LeanMapper\Reflection;

/**
 * @author Vojtěch Kohout
 */
class Property
{

	/** @var string */
	private $name;

	/** @var bool */
	private $writable;

	/** @var bool */
	private $collection;

	/** @var string */
	private $type;

	/** @var bool */
	private $basicType;

	/** @var bool */
	private $nullable;


	/**
	 * @param string $name
	 * @param bool $writable
	 * @param bool $collection
	 * @param string $type
	 * @param bool $nullable
	 * @param string $namespace
	 * @param array $aliases
	 */
	public function __construct($name, $writable, $collection, $type, $nullable, $namespace, array $aliases)
	{
		$this->name = $name;
		$this->writable = $writable;
		$this->collection = $collection;
		$this->type = $type;
		$this->nullable = $nullable;
		$this->initType($type, $namespace, $aliases);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return bool
	 */
	public function isWritable()
	{
		return $this->writable;
	}

	/**
	 * @return bool
	 */
	public function containsCollection()
	{
		return $this->collection;
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
	public function isNullable()
	{
		return $this->nullable;
	}

	////////////////////
	////////////////////

	/**
	 * @param string $type
	 * @param string $namespace
	 * @param array $aliases
	 */
	private function initType($type, $namespace, array $aliases)
	{
		if (preg_match('#^(boolean|bool|integer|int|float|string|array)$#', $type)) {
			if ($type === 'bool') {
				$type = 'boolean';
			}
			if ($type === 'int') {
				$type = 'integer';
			}
			$this->basicType = true;
		} else {
			if (substr($type, 0, 1) === '\\') {
				$type = substr($type, 1);
			} else {
				if (isset($aliases[$type])) {
					$type = $aliases[$type];
				} else {
					$type = $namespace . '\\' . $type;
				}
			}
			$this->basicType = false;
		}
		$this->type = $type;
	}

}
