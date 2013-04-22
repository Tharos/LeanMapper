<?php

namespace Model\Repository;

use DibiConnection;
use ORM\Result;
use Model\Entity\Application;

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

		$collection = new Result($row, 'application', $this->connection);

		return new Application($collection->getRow($id));
	}

	public function findAll()
	{
		$result = array();
		$rows = $this->connection->select('*')
				->from('application')
				->fetchAll();

		$collection = new Result($rows, 'application', $this->connection);

		foreach ($rows as $row) {
			$result[$row->id] = new Application($collection->getRow($row->id));
		}

		return $result;
	}

}
