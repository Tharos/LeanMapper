<?php

namespace Model\Entity;

use Model\Row;

/**
 * @author Vojtěch Kohout
 */
class Author
{

	private $row;


	public function __construct(Row $row)
	{
		$this->row = $row;
	}

	public function getName()
	{
		return $this->row->name;
	}

	public function getAuthorshipCount()
	{
		return count($this->row->referencing('application'));
	}

	public function getMaintainershipCount()
	{
		return count($this->row->referencing('application', null, 'maintainer_id'));
	}

}
