<?php

namespace Model;

use DibiFluent;

/**
 * @author Vojtěch Kohout
 */
class TagsFilter
{

	public function getName()
	{
		return 'tags';
	}

	public function limit(DibiFluent $statement, $limit, $offset = 0)
	{
		$statement->limit($limit)->offset($offset);
	}
	
}
