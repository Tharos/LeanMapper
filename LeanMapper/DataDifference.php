<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

use LeanMapper\Exception\InvalidArgumentException;

/**
 * Represents changes in M:N relationship
 *
 * @author Vojtěch Kohout
 */
class DataDifference
{

	/** @var array */
	private $added;

	/** @var array */
	private $removed;


	/**
	 * @param array $added
	 * @param array $removed
	 */
	public function __construct(array $added, array $removed)
	{
		$this->added = $added;
		$this->removed = $removed;
	}

	/**
	 * Performs quick lookup whether current instance may have any differences
	 *
	 * @return bool
	 */
	public function mayHaveAny()
	{
		return !empty($this->added) or !empty($this->removed);
	}

	/**
	 * Gets differences by given pivot
	 *
	 * @param mixed $pivot
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getByPivot($pivot)
	{
		$result = array();
		foreach ($this->added as $entry) {
			if (!isset($entry[$pivot])) {
				throw new InvalidArgumentException("Invalid pivot given: '$pivot'.");
			}
			if (isset($result[$entry[$pivot]])) {
				$result[$entry[$pivot]]++;
			} else {
				$result[$entry[$pivot]] = 1;
			}
		}
		foreach ($this->removed as $entry) {
			if (!isset($entry[$pivot])) {
				throw new InvalidArgumentException("Invalid pivot given: '$pivot'.");
			}
			if (isset($result[$entry[$pivot]])) {
				$result[$entry[$pivot]]--;
				if ($result[$entry[$pivot]] === 0) {
					unset($result[$entry[$pivot]]);
				}
			} else {
				$result[$entry[$pivot]] = -1;
			}
		}
		return $result;
	}

}
