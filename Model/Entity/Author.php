<?php

namespace Model\Entity;

use LeanMapper\Row;

/**
 * @author Vojtěch Kohout
 *
 * @property int $id
 * @property string $name
 * @property string $web
 * @property DibiDateTime|null $born
 */
class Author extends \LeanMapper\Entity
{

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
