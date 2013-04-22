<?php

namespace ORM;

use Nette\Callback;

/**
 * @author Vojtěch Kohout
 */
class Row
{

	/** @var Result */
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

	public function referenced($table, $filter = null, $viaColumn = null)
	{
		return $this->collection->getReferencedRow($this->id, $table, $filter, $viaColumn);
	}

	public function referencing($table, $filter = null, $viaColumn = null)
	{
		return $this->collection->getReferencingRows($this->id, $table, $filter, $viaColumn);
	}

}
