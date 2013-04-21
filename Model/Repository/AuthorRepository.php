<?php

namespace Model\Repository;

use DibiConnection;
use Model\Collection;
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

		$collection = new Collection($row, 'author', $this->connection);

		return new Author($collection->getRow($id));
	}

	public function findAll()
	{
		$result = array();
		$rows = $this->connection->select('*')
				->from('author')
				->fetchAll();

		$collection = new Collection($rows, 'author', $this->connection);

		foreach ($rows as $row) {
			$result[$row->id] = new Author($collection->getRow($row->id));
		}

		return $result;
	}

}
