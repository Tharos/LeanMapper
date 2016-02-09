<?php

use Tester\Assert;
use LeanMapper\Entity;

require_once __DIR__ . '/../bootstrap.php';

//////////

class BookRepository extends LeanMapper\Repository
{

	protected $defaultEntityNamespace = null;

	public function find($id)
	{
		$row = $this->createFluent()->where('%n = %i', $this->mapper->getPrimaryKey($this->getTable()), $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

/**
 * @property int $id
 * @property string $name
 */
class Author extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Author $author m:hasOne
 */
class Book extends Entity
{
}

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

//////////

$book = $bookRepository->find(1);

$bookRepository->delete($book);

Assert::exception(function () use ($bookRepository, $book) {
	$book->author->name;
}, 'LeanMapper\Exception\InvalidStateException', 'Missing entity factory in Book.');

$bookRepository->persist($book);

Assert::same('Andrew Hunt', $book->author->name);
