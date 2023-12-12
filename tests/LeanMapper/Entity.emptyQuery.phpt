<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$queries = [];

$connection->onEvent[] = function ($event) use (&$queries) {
    $queries[] = $event->sql;
};

//////////

class Author extends Entity
{
}

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

$book->makeAlive($entityFactory, $connection, $mapper);
$book->attach(1);

$book->getData();

Assert::equal(0, count($queries));
