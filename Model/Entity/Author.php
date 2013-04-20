<?php

namespace Model\Entity;

use DibiConnection;

/**
 * @author Vojtěch Kohout
 */
class Author
{

	private $connection;

	private $row;


	public function __construct($row, DibiConnection $connection)
	{
		$this->connection = $connection;
		$this->row = $row;
	}

	public function getName()
	{
		return $this->row->name;
	}

}
