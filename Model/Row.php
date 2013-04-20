<?php

namespace Model;

/**
 * @author Vojtěch Kohout
 */
class Row
{

	private $collection;

	private $id;


	public function __construct($collection, $id)
	{
		$this->collection = $collection;
		$this->id = $id;
	}

	function __get($name)
	{
		return $this->collection->getData($this->id, $name);
	}

}
