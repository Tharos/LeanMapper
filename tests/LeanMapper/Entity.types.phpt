<?php

use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property DateTimeImmutable $published
 */
class Book extends LeanMapper\Entity
{
}

//////////

$book = new Book;

$book->published = new \Dibi\DateTime;

Assert::type('\Dibi\DateTime', $book->published);

Assert::exception(
    function () use ($book) {
        $book->published = new ArrayObject;
    },
    'LeanMapper\Exception\InvalidValueException',
    "Unexpected value type given in property 'published' in entity Book, DateTimeImmutable expected, instance of ArrayObject given."
);

//////////

$dibiRow = new \Dibi\Row(
    [
        'published' => new ArrayObject,
    ]
);

$book = new Book(Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(Result::DETACHED_ROW_ID));

Assert::exception(
    function () use ($book) {
        $book->published;
    },
    'LeanMapper\Exception\InvalidValueException',
    "Property 'published' in entity Book is expected to contain an instance of DateTimeImmutable, instance of ArrayObject given."
);

//////////

$dibiRow = new \Dibi\Row(
    [
        'published' => new \Dibi\DateTime,
    ]
);

$book = new Book(Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(Result::DETACHED_ROW_ID));

Assert::type('\Dibi\DateTime', $book->published);
Assert::type('DateTimeImmutable', $book->published);
