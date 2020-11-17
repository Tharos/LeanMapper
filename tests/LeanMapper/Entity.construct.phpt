<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Result;
use LeanMapper\Row;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 */
class Book extends Entity
{
}

//////////

$book = new Book;

Assert::type(Book::class, $book);

//////////

$data = [
    'id' => 1,
    'name' => 'PHP guide',
    'pubdate' => '2013-06-13',
];

$book = new Book($data);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$dibiRow = new \Dibi\Row($data);
$row = new Row(Result::createInstance($dibiRow, 'book', $connection, $mapper), 1);
$book = new Book($row);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$dibiRow = new \Dibi\Row($data);
$row = Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(1);
$book = new Book($row);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$book = new Book(new ArrayObject($data));

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

Assert::exception(
    function () {
        new Book(false);
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'Argument $arg in Book::__construct must contain either null, array, instance of LeanMapper\Row or instance of Traversable, boolean given.'
);

Assert::exception(
    function () {
        new Book('hello');
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'Argument $arg in Book::__construct must contain either null, array, instance of LeanMapper\Row or instance of Traversable, string given.'
);

//////////

$dibiRow = new \Dibi\Row($data);
$row = new Row(Result::createInstance($dibiRow, 'book', $connection, $mapper), 1);
$row->detach();

Assert::exception(
    function () use ($row) {
        new Book($row);
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'It is not allowed to create entity Book from detached instance of LeanMapper\Row.'
);
