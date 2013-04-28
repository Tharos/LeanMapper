<?php

namespace Model\Repository;

use Model\Entity\Application;

/**
 * @author Vojtěch Kohout
 */
class ApplicationRepository extends \LeanMapper\Repository
{

	/**
	 * @param int $id
	 * @return Application
	 */
	public function find($id)
	{
		return $this->createEntity(
			$this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch()
		);
	}

	/**
	 * @return Application[]
	 */
	public function findAll()
	{
		return $this->createEntities(
			$this->connection->select('*')->from($this->getTable())->fetchAll()
		);
	}

}
