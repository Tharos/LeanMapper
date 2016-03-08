<?php

use LeanMapper\Entity;
use Tester\Assert;

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
 * @property string $name
 */
class Author extends Entity
{
}

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

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

Assert::exception(
    function () use ($author, $bookRepository) {
        $bookRepository->persist($author);
    },
    'LeanMapper\Exception\InvalidArgumentException',
    'Repository BookRepository can only handle Book entites. Use different repository to handle Author.'
);

Assert::exception(
    function () use ($author, $bookRepository) {
        $bookRepository->delete($author);
    },
    'LeanMapper\Exception\InvalidArgumentException',
    'Repository BookRepository can only handle Book entites. Use different repository to handle Author.'
);
