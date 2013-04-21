<?php

namespace Model\Entity;

use Model\Row;
use Model\TagsFilter;
use Nette\Callback;

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
		$row = $this->row->related('author', null, 'maintainer_id');
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
			$tagRow = $row->related('tag', function ($statement) {
				$statement->where('[name] = %s', 'PHP');
			});
			if ($tagRow !== null) {
				$tags[] = new Tag($tagRow);
			}
		}
		return $tags;
	}

}
