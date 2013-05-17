<?php

/**
 * This file is part of the Lean Mapper library
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 */

namespace LeanMapper;

use Closure;

/**
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Result */
	private $result;

	/** @var int */
	private $id;


	/**
	 * @param Result $result
	 * @param int $id
	 */
	public function __construct(Result $result, $id)
	{
		$this->result = $result;
		$this->id = $id;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		return $this->result->getData($this->id, $name);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	function __set($name, $value)
	{
		$this->result->setData($this->id, $name, $value);
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @return Row
	 */
	public function referenced($table, Closure $filter = null, $viaColumn = null)
	{
		return $this->result->getReferencedRow($this->id, $table, $filter, $viaColumn);
	}

	/**
	 * @param string $table
	 * @param Closure|null $filter
	 * @param string|null $viaColumn
	 * @return Row[]
	 */
	public function referencing($table, Closure $filter = null, $viaColumn = null)
	{
		return $this->result->getReferencingRows($this->id, $table, $filter, $viaColumn);
	}

}