<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class BaseRepository extends LeanMapper\Repository
{

	protected function createCollection(array $entites)
	{
		return new ArrayObject($entites);
	}

}

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 */
class Author extends LeanMapper\Entity
{
}

class AuthorRepository extends BaseRepository
{

	protected $defaultEntityNamespace = null;


	public function findAll()
	{
		return $this->createEntities(
			$this->connection->select('*')->from($this->getTable())->fetchAll()
		);
	}

}

//////////

$authorRepository = new AuthorRepository($connection, $mapper);

$authors = $authorRepository->findAll();

Assert::type('ArrayObject', $authors);

Assert::equal(5, count($authors));