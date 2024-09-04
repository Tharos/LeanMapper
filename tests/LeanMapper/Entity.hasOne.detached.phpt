<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

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
 * @property Author|null $reviewer m:hasOne
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


$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

////////////////////

$author = $authorRepository->find(1);

$book = new Book;
$book->author = $author;

Assert::same('Andrew Hunt', $book->author->name);

////////////////////

$author = new Author;
$author->name = 'Fowler';
$authorRepository->persist($author);

$book = new Book;
$book->author = $author;
Assert::same('Fowler', $book->author->name);

////////////////////

test('Assign NULL to detached', function () {
    $book = new Book;
    $book->reviewer = null;

    Assert::null($book->reviewer);
});


test('Assign attached & NULL to detached', function () use ($authorRepository) {
    $author = $authorRepository->find(1);

    $book = new Book;
    $book->author = $author;
    $book->reviewer = null;

    Assert::same('Andrew Hunt', $book->author->name);
    Assert::null($book->reviewer);
});
