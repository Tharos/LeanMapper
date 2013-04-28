<?php

namespace Model\Repository;

use Model\Entity\Author;

/**
 * @author Vojtěch Kohout
 */
class AuthorRepository extends \LeanMapper\Repository
{

	/**
	 * @param int $id
	 * @return Author
	 */
	public function find($id)
	{
		return $this->createEntity(
			$this->connection->select('*')->from('author')->where('id = %i', $id)->fetch()
		);
	}

	/**
	 * @return Author[]
	 */
	public function findAll()
	{
		return $this->createEntities(
			$this->connection->select('*')->from('author')->fetchAll()
		);
	}

}
