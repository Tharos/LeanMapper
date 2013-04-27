<?php

namespace Model\Entity;

/**
 * @author Vojtěch Kohout
 *
 * @property int $id
 * @property string $name
 */
class Tag extends \LeanMapper\Entity
{

	public function getUsageCount()
	{
		return count($this->row->referencing('application_tag'));
	}

}
