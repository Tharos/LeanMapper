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
use LeanMapper\Exception\RuntimeException;

/**
 * Set of entity filters
 *
 * @author Vojtěch Kohout
 */
class PropertyFilters
{

	/** @var string[] */
	private $filters = array();


	public function __construct($definition, Aliases $aliases)
	{
		$pieces = preg_split('#\s*,\s*#', trim($definition));
		foreach ($pieces as $filter) {
			if ($filter === '') {
				throw new InvalidAnnotationException('Empty filter definition given.');
			}
			$matches = array();
			preg_match('#^(?:((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)::)?((?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+)$#', $filter, $matches);
			if (empty($matches) or ($matches[1] !== '' and strpos($matches[2], '\\') !== false)) {
				throw new InvalidAnnotationException('Invalid filter definition given: ' . $filter);
			}
			$function = $matches[2];
			if ($matches[1] !== '') {
				$class = $matches[1];
				if (substr($class, 0, 1) === '\\') {
					$class = substr($class, 1);
				} else {
					$class = $aliases->translate($class);
				}
				$this->filters[] = $class . '::' . $function;
			} else {
				if (substr($function, 0, 1) === '\\') {
					$function = substr($function, 1);
				} else {
					$function = $aliases->translate($function);
				}
				$this->filters[] = $function;
			}
		}
	}

	/**
	 * Returns array of entity filters (array of callable strings)
	 *
	 * @return string[]
	 */
	public function getFilters()
	{
		return $this->filters;
	}

}
