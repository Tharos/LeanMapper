<?php

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

/**
 * @property int $id
 * @property string $name
 */
class Book extends LeanMapper\Entity
{
}

class LibraryRepository extends Repository
{

    public function findAuthor($id)
    {
        return $this->find('author', $id);
    }


    public function findBook($id)
    {
        return $this->find('book', $id);
    }


    public function findAuthors()
    {
        return $this->findAll('author');
    }


    public function findBooks()
    {
        return $this->findAll('book');
    }


    private function find($table, $id)
    {
        $row = $this->connection->select('*')->from($table)->where('id = %i', $id)->fetch();

        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }

        return $this->createEntity($row, null, $table);
    }


    private function findAll($table)
    {
        return $this->createEntities(
            $this->connection->select('*')->from($table)->orderBy('id')->fetchAll(),
            null,
            $table
        );
    }

}

//////////

$repository = new LibraryRepository($connection, $mapper, $entityFactory);

$authors = $repository->findAuthors();
$names = [];

foreach ($authors as $author) {
    Assert::type('Author', $author);
    $names[] = $author->name;
}

Assert::same([
    'Andrew Hunt',
    'Donald Knuth',
    'Martin Fowler',
    'Kent Beck',
    'Thomas H. Cormen',
], $names);

//////////

$books = $repository->findBooks();
$names = [];

foreach ($books as $book) {
    Assert::type('Book', $book);
    $names[] = $book->name;
}

Assert::same([
    'The Pragmatic Programmer',
    'The Art of Computer Programming',
    'Refactoring: Improving the Design of Existing Code',
    'Introduction to Algorithms',
    'UML Distilled',
], $names);

//////////

$author = $repository->findAuthor(3);
Assert::type('Author', $author);
Assert::same(3, $author->id);
Assert::same('Martin Fowler', $author->name);

//////////

$book = $repository->findBook(2);
Assert::type('Book', $book);
Assert::same(2, $book->id);
Assert::same('The Art of Computer Programming', $book->name);
