<?php

namespace Model\Repository;


/**
 * @author Vojtěch Kohout
 */
class ApplicationRepository extends \LeanMapper\Repository
{

	public function find($id)
	{
		$row = $this->connection->select('*')
				->from('application')
				->where('id = %i', $id)->fetch();

		return $this->createEntity($row, 'Model\Entity\Application', 'application');
	}

	public function findAll()
	{
		$rows = $this->connection->select('*')
				->from('application')
				->fetchAll();

		return $this->createEntities($rows, 'Model\Entity\Application', 'application');
	}

}
