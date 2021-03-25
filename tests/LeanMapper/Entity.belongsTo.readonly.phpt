<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property Book[] $books m:belongsToMany
 * @property Book $book m:belongsToOne
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

    public function find($id)
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($row);
    }

}

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

//////////

$author = $authorRepository->find(1);
$reflection = $author->getReflection();
Assert::false($reflection->getEntityProperty('books')->isWritable());
Assert::false($reflection->getEntityProperty('book')->isWritable());

//////////

$author = $authorRepository->find(1);
$book = $bookRepository->find(1);

Assert::error(
    function () use ($author, $book) {
        $author->books[] = $book;
    },
    E_NOTICE,
    'Indirect modification of overloaded property Author::$books has no effect'
);

Assert::exception(
    function () use ($author, $book) {
        $author->books = $book;
    },
    LeanMapper\Exception\MemberAccessException::class,
    "Cannot write to read-only property 'books' in entity Author."
);

Assert::exception(
    function () use ($author, $book) {
        $author->book = $book;
    },
    LeanMapper\Exception\MemberAccessException::class,
    "Cannot write to read-only property 'book' in entity Author."
);
