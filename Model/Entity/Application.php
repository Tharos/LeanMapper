<?php

namespace Model\Entity;

use DibiConnection;

/**
 * @author Vojtěch Kohout
 */
class Application
{

	private $connection;

	private $row;


	public function __construct($row, DibiConnection $connection)
	{
		$this->connection = $connection;
		$this->row = $row;
	}

	public function getTitle()
	{
		return $this->row->title;
	}

	public function getWeb()
	{
		return $this->row->web;
	}

	public function getAuthor()
	{
		$row = $this->connection->select('*')->from('author')->where('id = %i', $this->row->author_id)->fetch();
		return new Author($row, $this->connection);
	}

}
