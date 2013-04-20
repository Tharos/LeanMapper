<?php

namespace Model\Entity;

use Model\Row;

/**
 * @author Vojtěch Kohout
 */
class Application
{

	private $row;


	public function __construct(Row $row)
	{
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
		return new Author($this->row->author);
	}

}
