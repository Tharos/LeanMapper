<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class BaseEntity extends LeanMapper\Entity
{

	protected function createCollection(array $entites)
	{
		return new ArrayObject($entites);
	}

}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 * @property Author $author m:hasOne
 */
class Book extends BaseEntity
{
}

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 * @property Book[] $books m:belongsToMany
 */
class Author extends BaseEntity
{
}

class AuthorRepository extends LeanMapper\Repository
{

	protected $defaultEntityNamespace = null;


	public function find($id)
	{
		return $this->createEntity(
			$this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch()
		);
	}

}

//////////

$authorRepository = new AuthorRepository($connection);

$author = $authorRepository->find(1);

$books = $author->books;

Assert::type('ArrayObject', $books);

Assert::equal(1, count($books));

//////////

$books = $authorRepository->find(3)->books;

Assert::type('ArrayObject', $books);

Assert::equal(2, count($books));