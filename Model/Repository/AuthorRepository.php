<?php

namespace Model\Repository;

use DibiConnection;
use ORM\Result;
use Model\Entity\Author;

/**
 * @author Vojtěch Kohout
 */
class AuthorRepository
{

	private $connection;


	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	public function find($id)
	{
		$row = $this->connection->select('*')
				->from('author')
				->where('id = %i', $id)->fetch();

		$result = new Result($row, 'author', $this->connection);

		return new Author($result->getRow($id));
	}

	public function findAll()
	{
		$result = array();
		$rows = $this->connection->select('*')
				->from('author')
				->fetchAll();

		$collection = new Result($rows, 'author', $this->connection);

		foreach ($rows as $row) {
			$result[$row->id] = new Author($collection->getRow($row->id));
		}

		return $result;
	}

}
