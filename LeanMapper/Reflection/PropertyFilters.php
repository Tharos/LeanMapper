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
			if ($set === '') {
				$this->filters[] = $this->annotationArgs[] = array();
				continue;
			}
			$filters = $annotationArgs = array();
			foreach (preg_split('#\s*,\s*#', $set) as $filter) {
				$matches = array();
				preg_match('~^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:#(.*))?$~', $filter, $matches);
				if (empty($matches)) {
					throw new InvalidAnnotationException("Malformed filter name given: '$filter'.");
				}
				$filters[] = $filterName = $matches[1];
				if (isset($matches[2])) {
					$annotationArgs[$filterName] = $matches[2];
				}
			}
			$this->filters[] = $filters;
			$this->annotationArgs[] = $annotationArgs;
		}
	}

	/**
	 * Gets array of entity's filters (array of filter names)
	 *
	 * @param int $index
	 * @return array
	 */
	public function getFilters($index = 0)
	{
		if (!isset($this->filters[$index])) {
			return array();
		}
		return $this->filters[$index];
	}

	/**
	 * Gets filters arguments hard-coded in annotation
	 *
	 * @param int $index
	 * @return array
	 */
	public function getFiltersAnnotationArgs($index = 0)
	{
		if (!isset($this->annotationArgs[$index])) {
			return array();
		}
		return $this->annotationArgs[$index];
	}

}
