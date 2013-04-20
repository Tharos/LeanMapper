<?php

namespace Model;

/**
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Collection */
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

	public function related($table, $viaColumn = null)
	{
		return $this->collection->getRelatedRow($this->id, $table, $viaColumn);
	}

	public function referencing($table, $viaColumn = null)
	{
		return $this->collection->getReferencingRows($this->id, $table, $viaColumn);
	}

}
