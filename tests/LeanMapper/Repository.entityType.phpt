<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class Repository extends LeanMapper\Repository
{
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


$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

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
    LeanMapper\Exception\InvalidArgumentException::class,
    'Repository BookRepository can only handle Book entites. Use different repository to handle Author.'
);

Assert::exception(
    function () use ($author, $bookRepository) {
        $bookRepository->delete($author);
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'Repository BookRepository can only handle Book entites. Use different repository to handle Author.'
);
