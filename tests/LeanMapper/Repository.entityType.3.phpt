<?php

use LeanMapper\Repository;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

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
 */
class Book extends LeanMapper\Entity
{
}

/**
 * @property Author $reviewer m:hasOne
 */
class ReviewedBook extends Book
{
}


class LibraryRepository extends Repository
{

    public function findReviewedBook($id)
    {
        $row = $this->connection->select('*')->from('book')->where('id = %i', $id)->where('reviewer_id IS NOT NULL')->fetch();

        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }

        return $this->createEntity($row, 'ReviewedBook', 'book');
    }


    public function findBook($id)
    {
        $row = $this->connection->select('*')->from('book')->where('id = %i', $id)->fetch();

        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }

        return $this->createEntity($row, 'Book', 'book');
    }


    public function findReviewedBooks()
    {
        return $this->createEntities(
            $this->connection->select('*')->from('book')->where('reviewer_id IS NOT NULL')->orderBy('id')->fetchAll(),
            'ReviewedBook',
            'book'
        );
    }


    public function findBooks()
    {
        return $this->createEntities(
            $this->connection->select('*')->from('book')->orderBy('id')->fetchAll(),
            'Book',
            'book'
        );
    }

}

//////////

$repository = new LibraryRepository($connection, $mapper, $entityFactory);

$reviewedBooks = $repository->findReviewedBooks();
$names = [];

foreach ($reviewedBooks as $reviewedBook) {
    Assert::type('ReviewedBook', $reviewedBook);
    $names[] = $reviewedBook->name;
}

Assert::same([
    'The Art of Computer Programming',
    'Refactoring: Improving the Design of Existing Code',
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

$reviewedBook = $repository->findReviewedBook(3);
Assert::type('ReviewedBook', $reviewedBook);
Assert::same(3, $reviewedBook->id);
Assert::same('Refactoring: Improving the Design of Existing Code', $reviewedBook->name);

//////////

$book = $repository->findBook(2);
Assert::type('Book', $book);
Assert::same(2, $book->id);
Assert::same('The Art of Computer Programming', $book->name);
