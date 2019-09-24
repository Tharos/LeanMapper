<?php

declare(strict_types=1);

use LeanMapper\Entity;
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

$data = [
    'id' => 1,
    'name' => 'PHP guide',
    'pubdate' => '2013-06-13',
];

$book = new Book;

$book->assign($data);

Assert::equal($data, $book->getData());

//////////

$book = new Book;

$book->assign(new ArrayObject($data));

Assert::equal($data, $book->getData());

//////////

$book = new Book;

Assert::exception(
    function () use ($book) {
        $book->assign(false);
    },
    'LeanMapper\Exception\InvalidArgumentException',
    'Argument $values in Book::assign must contain either array or instance of Traversable, boolean given.'
);

Assert::exception(
    function () use ($book) {
        $book->assign('hello');
    },
    'LeanMapper\Exception\InvalidArgumentException',
    'Argument $values in Book::assign must contain either array or instance of Traversable, string given.'
);
