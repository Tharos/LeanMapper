<?php

namespace Model;

use Nette\Callback;

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

	public function related($table, $filter = null, $viaColumn = null)
	{
		return $this->collection->getRelatedRow($this->id, $table, $filter, $viaColumn);
	}

	public function referencing($table, $filter = null, $viaColumn = null)
	{
		return $this->collection->getReferencingRows($this->id, $table, $filter, $viaColumn);
	}

}
