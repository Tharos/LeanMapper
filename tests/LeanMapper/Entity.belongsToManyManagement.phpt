<?php

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

class Mapper extends DefaultMapper
{

	protected $defaultEntityNamespace = null;

}

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:belongsToMany
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

class AuthorRepository extends \LeanMapper\Repository
{

	/**
	 * @param $id
	 *
	 * @return Author
	 * @throws Exception
	 */
	public function find($id)
	{
		$row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

class BookRepository extends \LeanMapper\Repository
{

	/**
	 * @param $id
	 *
	 * @return Book
	 * @throws Exception
	 */
	public function find($id)
	{
		$row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

function implodeBooks(array $books) {
	$result = array();
	foreach ($books as $book) {
		$result[] = $book->name;
	}
	return implode(' || ', $result);
}

////////////////////

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

$author = $authorRepository->find(3);

foreach($author->books as $book) {
	if($book->id == 5) {
		$bookRepository->delete($book);
	}
}

Assert::equal('Refactoring: Improving the Design of Existing Code', implodeBooks($author->books));
