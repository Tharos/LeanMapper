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
 * Set of property filters
 *
 * @author Vojtěch Kohout
 */
class PropertyFilters
{

	/** @var string[] */
	private $filters = array();


	/**
	 * @param string $definition
	 * @throws InvalidAnnotationException
	 */
	public function __construct($definition)
	{
		foreach (preg_split('#\s*\|\s*#', trim($definition)) as $set) {
			if ($set === '') {
				$this->filters[] = array();
				continue;
			}
			$filters = array();
			foreach (preg_split('#\s*,\s*#', trim($set)) as $filter) {
				if ($filter === '') {
					throw new InvalidAnnotationException('Empty filter name given.');
				}
				$filters[] = $filter;
			}
			// TODO: check count($filters) > 2
			$this->filters[] = $filters;
		}
	}

	/**
	 * Returns array of entity filters (array of filter names)
	 *
	 * @param int|null $index
	 * @return string[]
	 */
	public function getFilters($index = null)
	{
		if ($index === null) {
			return $this->filters;
		}
		if (!isset($this->filters[$index])) {
			return array();
		}
		return $this->filters[$index];
	}

}
