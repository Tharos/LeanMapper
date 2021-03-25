<?php

declare(strict_types=1);

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property string $id
 * @property string $name
 */
class Book extends LeanMapper\Entity
{
}

//////////

$book = new Book;
$book->name = 'My Book';
$book->makeAlive($entityFactory, $connection, $mapper);
$book->attach('my-book');

Assert::equal(
    [
        'id' => 'my-book',
        'name' => 'My Book',
    ],
    $book->getData()
);
