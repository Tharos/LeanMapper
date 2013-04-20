<?php

namespace Model\Repository;

use DibiConnection;
use Model\Collection;
use Model\Entity\Application;
use ArrayObject;

/**
 * @author Vojtěch Kohout
 */
class ApplicationRepository
{

	private $connection;


	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}

	public function find($id)
	{
		$row = $this->connection->select('*')
				->from('application')
				->where('id = %i', $id)->fetch();

		return new Application($row, $this->connection);
	}

	public function findAll()
	{
		$result = new ArrayObject;
		$rows = $this->connection->select('*')
				->from('application')
				->fetchAll();

		$collection = new Collection($rows, $this->connection);

		foreach ($rows as $row) {
			$result[$row->id] = new Application($collection->getRow($row->id));
		}

		return $result;
	}

}
