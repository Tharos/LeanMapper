<?php

use LeanMapper\Entity;
use LeanMapper\Repository;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 * @property Book[] $books m:belongsToMany
 */
class Author extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 * @property NULL|Author $author m:hasOne
 */
class Book extends Entity
{
}

class BookRepository extends \LeanMapper\Repository
{

	public function find($id)
	{
		$row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

//////////

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

$book = $bookRepository->find(1);

Assert::type('Author', $book->author);

$author = $book->author;

$book->author = null;

Assert::equal(null, $book->author);

Assert::equal(array (
	'author_id' => null,
), $book->getModifiedRowData());

$book->author = $author;

Assert::type('Author', $book->author);