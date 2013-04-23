<?php

namespace Model\Entity;

use ORM\Row;

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

	public function getReferencingTags()
	{
		$tags = array();
		foreach ($this->row->referencing('application') as $application) {
			foreach ($application->referencing('application_tag') as $tagRelation) {
				$tags[$tagRelation->tag_id] = new Tag($tagRelation->referenced('tag'));
			}
		}
		return $tags;
	}

}
