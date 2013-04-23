<?php

namespace Model\Entity;

use ORM\Row;

/**
 * @author Vojtěch Kohout
 */
class Tag
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

	public function getUsageCount()
	{
		return count($this->row->referencing('application_tag'));
	}

}
