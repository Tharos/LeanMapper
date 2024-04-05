<?php

declare(strict_types=1);

use LeanMapper\Connection;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

////////////////////

/**
 * @property int $id
 */
class Tag extends Entity
{
}

/**
 * @property int $id
 * @property Tag[] $tags m:hasMany m:filter(first#foobar,second|first)
 */
class Book extends Entity
{
}

////////////////////

$args = new ArrayObject;

$connection->registerFilter(
    'first',
    function () use ($args) {
        $args->append(func_get_args());
    },
    'ep'
);

$connection->registerFilter(
    'second',
    function () use ($args) {
        $args->append(func_get_args());
    }
);

$book = new Book;
$book->makeAlive($entityFactory, $connection, $mapper);
$book->attach(1);

$book->getTags(1, 'argument', true);

$args = (array)$args;

Assert::equal(4, count($args));

Assert::equal(7, count($args[0]));

Assert::type(LeanMapper\Fluent::class, $args[0][0]);
Assert::type(Book::class, $args[0][1]);
Assert::type(LeanMapper\Reflection\Property::class, $args[0][2]);
Assert::equal('foobar', $args[0][3]);
Assert::equal(1, $args[0][4]);
Assert::equal('argument', $args[0][5]);
Assert::equal(true, $args[0][6]);

Assert::type(LeanMapper\Fluent::class, $args[1][0]);
Assert::equal(1, $args[1][1]);
Assert::equal('argument', $args[1][2]);
Assert::equal(true, $args[1][3]);

Assert::type(LeanMapper\Fluent::class, $args[2][0]);
Assert::type(Book::class, $args[2][1]);
Assert::type(LeanMapper\Reflection\Property::class, $args[2][2]);
Assert::equal(1, $args[2][3]);
Assert::equal('argument', $args[2][4]);
Assert::equal(true, $args[2][5]);

//////////

function lmFilter()
{
}

$connection->registerFilter('third', 'lmFilter', Connection::WIRE_ENTITY);
Assert::equal('e', $connection->getWiringSchema('third'));

$connection->registerFilter('fourth', 'lmFilter', Connection::WIRE_PROPERTY);
Assert::equal('p', $connection->getWiringSchema('fourth'));

$connection->registerFilter('fifth', 'lmFilter', Connection::WIRE_ENTITY | Connection::WIRE_PROPERTY);
Assert::equal('ep', $connection->getWiringSchema('fifth'));

$connection->registerFilter('sixth', 'lmFilter', Connection::WIRE_ENTITY_AND_PROPERTY);
Assert::equal('ep', $connection->getWiringSchema('sixth'));

$connection->registerFilter('seventh', 'lmFilter');
Assert::equal('', $connection->getWiringSchema('seventh'));
