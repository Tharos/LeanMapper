<?php

namespace Model\Repository;

use DibiConnection;
use LeanMapper\Result;
use Model\Entity\Author;

/**
 * @author Vojtěch Kohout
 */
class AuthorRepository extends \LeanMapper\Repository
{

	public function find($id)
	{
		$row = $this->connection->select('*')
				->from('author')
				->where('id = %i', $id)->fetch();

		return $this->createEntity($row, 'Model\Entity\Author', 'author');
	}

	public function findAll()
	{
		$rows = $this->connection->select('*')
				->from('author')
				->fetchAll();

		return $this->createEntities($rows, 'Model\Entity\Author', 'author');
	}

}
