<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$i = 1;
$connection->onEvent[] = function ($event) use (&$queries, &$i) {
    $queries[] = $event->sql;
    $i++;
};

//////////

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
 */
class Book extends Entity
{
}

//////////

$result = $connection->select(
    '[author.id] [author_id], [author.name] [author_name], [book.id] [book_id], [book.name] [book_name], [book.author_id] [book_author_id]'
)
    ->from('author')
    ->join('book')->on('[book.author_id] = [author.id]')
    ->where('[book.name] != %s', 'The Pragmatic Programmer')
    ->where('LENGTH([book].[name]) > %i', 13)
    ->fetchAll();

$authors = [];
$books = [];

foreach ($result as $row) {
    if (!isset($authors[$row['author_id']])) {
        $authors[$row['author_id']] = new \Dibi\Row(
            [
                'id' => $row['author_id'],
                'name' => $row['author_name'],
            ]
        );
    }
    if (!isset($books[$row['book_id']])) {
        $books[$row['book_id']] = new \Dibi\Row(
            [
                'id' => $row['book_id'],
                'name' => $row['book_name'],
                'author_id' => $row['book_author_id'],
            ]
        );
    }
}
$authorsResult = Result::createInstance($authors, 'author', $connection, $mapper);
$booksResult = Result::createInstance($books, 'book', $connection, $mapper);

$authorsResult->setReferencingResult($booksResult, 'book', 'author_id');

$entities = [];

foreach ($authors as $author) {
    $entity = $entityFactory->createEntity('Author', $authorsResult->getRow($author->id));
    $entity->makeAlive($entityFactory);
    $entities[$author->id] = $entity;
}
$authors = $entityFactory->createCollection($entities);

//////////

$output = [];

foreach ($authors as $author) {
    $outputBooks = [];
    foreach ($author->books as $book) {
        $outputBooks[] = $book->name;
    }
    $output[$author->name] = $outputBooks;
}

Assert::equal(
    [
        'Donald Knuth' => ['The Art of Computer Programming'],
        'Martin Fowler' => ['Refactoring: Improving the Design of Existing Code'],
        'Thomas H. Cormen' => ['Introduction to Algorithms'],
    ],
    $output
);

Assert::equal(
    "SELECT [author].[id] [author_id], [author].[name] [author_name], [book].[id] [book_id], [book].[name] [book_name], [book].[author_id] [book_author_id] FROM [author] JOIN [book] ON [book].[author_id] = [author].[id] WHERE [book].[name] != 'The Pragmatic Programmer' AND LENGTH([book].[name]) > 13",
    reset($queries)
);
