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

	/** @var array */
	private $filters = array();

	/** @var array */
	private $annotationArgs = array();


	/**
	 * @param string $definition
	 * @throws InvalidAnnotationException
	 */
	public function __construct($definition)
	{
		foreach (preg_split('#\s*\|\s*#', trim($definition)) as $set) {
			$matches = array();
			preg_match('~^(.*?)(?:#(.*))?$~', $set, $matches);

			if ($matches[1] === '') {
				$this->filters[] = array();
				$this->annotationArgs[] = null;
				continue;
			}
			$filters = array();
			foreach (preg_split('#\s*,\s*#', trim($matches[1])) as $filter) {
				if ($filter === '') {
					throw new InvalidAnnotationException('Empty filter name given.');
				}
				$filters[] = $filter;
			}
			$this->filters[] = $filters;
			$this->annotationArgs[] = isset($matches[2]) ? $matches[2] : null;
		}
	}

	/**
	 * Returns array of entity filters (array of filter names)
	 *
	 * @param int|null $index
	 * @return array
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

	/**
	 * @param int|null $index
	 * @return array|string|null
	 */
	public function getFiltersAnnotationArg($index = null)
	{
		if ($index === null) {
			return $this->annotationArgs;
		}
		if (!isset($this->annotationArgs[$index])) {
			return null;
		}
		return $this->annotationArgs[$index];
	}

}
