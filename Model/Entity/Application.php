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
		return new Author($this->row->related('author'));
	}

	public function getMaintainer()
	{
		$row = $this->row->related('author', 'maintainer_id');
		if ($row === null) {
			return null;
		}
		return new Author($row);
	}

	public function getTags()
	{
		$rows = $this->row->referencing('application_tag');
		$tags = array();
		foreach ($rows as $row) {
			$tags[] = new Tag($row->related('tag'));
		}
		return $tags;
	}

}
