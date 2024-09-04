<?php

declare(strict_types=1);

use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();

//////////

/**
 * @property non-empty-string $name
 */
class Book extends LeanMapper\Entity
{
}

//////////

test('Detached entity', function () {
    $book = new Book;
    $book->name = 'BookName';

    Assert::same('BookName', $book->name);

    Assert::exception(
        function () use ($book) {
            $book->name = '';
        },
        LeanMapper\Exception\InvalidValueException::class,
        "Unexpected value type given in property 'name' in entity Book, non-empty-string expected, string given."
    );
});


test('Invalid row', function () use ($connection, $mapper) {
    $dibiRow = new \Dibi\Row([
        'name' => 'My book',
    ]);

    $book = new Book(Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(Result::DETACHED_ROW_ID));

    Assert::same('My book', $book->name);
});


test('Invalid row', function () use ($connection, $mapper) {
    $dibiRow = new \Dibi\Row([
        'name' => '',
    ]);

    $book = new Book(Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(Result::DETACHED_ROW_ID));

    Assert::exception(
        function () use ($book) {
            $book->name;
        },
        LeanMapper\Exception\InvalidValueException::class,
        "Property 'name' in entity Book is expected to contain an non-empty-string, string given."
    );
});
