<?php

declare(strict_types=1);

use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();

//////////

/**
 * @property DateTimeImmutable $published
 * @property int $year
 * @property boolean $active
 */
class Book extends LeanMapper\Entity
{
}

//////////

$book = new Book;

$book->published = new \Dibi\DateTime;
$book->year = 2021;
$book->active = true;

Assert::type(\Dibi\DateTime::class, $book->published);
Assert::type('int', $book->year);
Assert::type('bool', $book->active);

Assert::exception(
    function () use ($book) {
        $book->published = new ArrayObject;
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Unexpected value type given in property 'published' in entity Book, DateTimeImmutable expected, instance of ArrayObject given."
);

Assert::exception(
    function () use ($book) {
        $book->year = '2021';
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Unexpected value type given in property 'year' in entity Book, integer expected, string given."
);


Assert::exception(
    function () use ($book) {
        $book->year = new ArrayObject;
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Unexpected value type given in property 'year' in entity Book, integer expected, instance of ArrayObject given."
);

Assert::exception(
    function () use ($book) {
        $book->active = new ArrayObject;
    },
    LeanMapper\Exception\InvalidValueException::class,
    "Unexpected value type given in property 'active' in entity Book, boolean expected, instance of ArrayObject given."
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
    LeanMapper\Exception\InvalidValueException::class,
    "Property 'published' in entity Book is expected to contain an instance of DateTimeImmutable, instance of ArrayObject given."
);

//////////

$dibiRow = new \Dibi\Row(
    [
        'published' => new \Dibi\DateTime,
    ]
);

$book = new Book(Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(Result::DETACHED_ROW_ID));

Assert::type(\Dibi\DateTime::class, $book->published);
Assert::type(DateTimeImmutable::class, $book->published);
