<?php

use Tester\Assert;
use LeanMapper\Entity;

require_once __DIR__ . '/../bootstrap.php';

//////////

class Repository extends LeanMapper\Repository
{

	protected $defaultEntityNamespace = null;

}

class BookRepository extends Repository
{
}

class AuthorRepository extends Repository
{
}

/**
 * @property int $id
 * @property int $name
 */
class Author extends Entity
{
}

$authorRepository = new AuthorRepository($connection);
$bookRepository = new BookRepository($connection);

//////////

$author = new Author;
$author->name = 'Jacob Veldhuyzen van Zanten';

$authorRepository->persist($author);

Assert::equal(false, $author->isDetached());

$authorRepository->delete($author);

Assert::equal(true, $author->isDetached());

//////////

$author = new Author;
$author->name = 'Victor Grubbs';

Assert::exception(function () use ($author, $bookRepository) {
	$bookRepository->persist($author);
}, 'LeanMapper\Exception\InvalidArgumentException', 'Repository BookRepository cannot handle Author entity.');

Assert::exception(function () use ($author, $bookRepository) {
	$bookRepository->delete($author);
}, 'LeanMapper\Exception\InvalidArgumentException', 'Repository BookRepository cannot handle Author entity.');
