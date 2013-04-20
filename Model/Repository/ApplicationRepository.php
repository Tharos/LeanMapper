<?php

namespace Model\Repository;

use DibiConnection;
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
		$row = $this->connection->select('*')->from('application')->where('id = %i', $id)->fetch();
		return new Application($row, $this->connection);
	}

	public function findAll()
	{
		$collection = new ArrayObject;
		$rows = $this->connection->select('*')->from('application')->fetchAll();
		foreach ($rows as $row) {
			$collection[$row->id] = new Application($row, $this->connection);
		}
		return $collection;
	}

}
