<?php

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection->onEvent[] = function ($event) use (&$queries) {
	$queries[] = $event->sql;
};

//////////

/**
 * @property int $id
 * @property Author|null $author m:hasOne
 * @property string $name
 */
class Book extends Entity
{
}

//////////

$book = new Book;
$book->name = 'Test book';
$book->author = null;

$book->alive($connection, $mapper, $entityFactory);
$book->attach(1, 'book');

$book->getData();

Assert::equal(0, count($queries));