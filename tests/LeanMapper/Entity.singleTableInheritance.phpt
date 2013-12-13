<?php

use LeanMapper\Entity;
use LeanMapper\Repository;
use LeanMapper\Row;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class Mapper extends LeanMapper\DefaultMapper
{

	protected $defaultEntityNamespace = null;


	public function getEntityClass($table, Row $row = null)
	{
		if ($table === 'author' and $row !== null) {
			if ($row->web !== null) {
				return 'AuthorWithWeb';
			}
		}
		return parent::getEntityClass($table, $row);
	}

}

/**
 * @property int $id
 * @property string $name
 */
class Author extends LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 * @property Author $author m:hasOne
 */
class Book extends LeanMapper\Entity
{
}

/**
 * @property string $web
 */
class AuthorWithWeb extends Author
{
}

class BaseRepository extends LeanMapper\Repository
{

	public function findAll()
	{
		return $this->createEntities(
			$this->connection->select('*')
				->from($this->getTable())
				->fetchAll()
		);
	}

}

class AuthorRepository extends BaseRepository
{
}

class BookRepository extends BaseRepository
{
}

//////////

$mapper = new Mapper;

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

foreach ($authorRepository->findAll() as $author) {
	if ($author->id === 3 or $author->id === 6) {
		Assert::type('AuthorWithWeb', $author);
		Assert::true(is_string($author->web));
	} else {
		Assert::type('Author', $author);
		Assert::throws(function () use ($author) {
			$author->web;
		}, 'LeanMapper\Exception\MemberAccessException', "Cannot access undefined property 'web' in entity Author.");
	}
}

foreach ($bookRepository->findAll() as $book) {
	$author = $book->author;
	if ($author->id === 3 or $author->id === 6) {
		Assert::type('AuthorWithWeb', $author);
		Assert::true(is_string($author->web));
	} else {
		Assert::type('Author', $author);
		Assert::throws(function () use ($author) {
			$author->web;
		}, 'LeanMapper\Exception\MemberAccessException', "Cannot access undefined property 'web' in entity Author.");
	}
}
