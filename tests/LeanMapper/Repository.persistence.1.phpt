<?php

use LeanMapper\Entity;
use LeanMapper\Repository;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 */
class Author extends LeanMapper\Entity
{
}

class AuthorRepository extends Repository
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

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$authors = $authorRepository->findAll();

$author = $authors[3];

$author->detach();

$errorMessage = PHP_VERSION_ID < 50500 ? 'PRIMARY KEY must be unique' : 'UNIQUE constraint failed: author.id';
Assert::exception(function () use ($authorRepository, $author) {
	$authorRepository->persist($author);
}, '\Dibi\DriverException', $errorMessage);

//////////

$author->id = 6;
$author->name = 'John Doe';

Assert::true($author->isDetached());

$authorRepository->persist($author);

Assert::false($author->isDetached());

Assert::equal('John Doe', $authors[3]->name);

//////////

$author = new Author(array(
	'name' => 'Steve Lee',
));

$authorRepository->persist($author);

Assert::equal(7, $author->id);

$authorRepository->persist($author);

Assert::equal(7, $author->id);

//////////

Assert::exception(function () use ($authorRepository, $author) {
	$author->id = 8;
}, 'LeanMapper\Exception\InvalidArgumentException', "ID can only be set in detached rows.");
