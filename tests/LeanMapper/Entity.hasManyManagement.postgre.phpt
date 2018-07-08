<?php

use LeanMapper\Connection;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../libs/DummyDrivers.php';

$driver = new PostgreDummyDriver;
$connection = new Connection(array(
    'driver' => $driver,
));

////////////////////

/**
 * @property int $id
 * @property string $name
 */
class Tag extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Tag[] $tags m:hasMany
 */
class Book extends Entity
{
}

class BookRepository extends \LeanMapper\Repository
{

    public function find($id)
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('[id] = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($row);
    }

}

////////////////////

$driver->setResultData('SELECT * FROM "book" WHERE "id" = 2 LIMIT 1', [
    [
        'id' => 2,
        'name' => 'Lorem ipsum dolor',
    ]
]);

$driver->setResultData('SELECT "book_tag".* FROM "book_tag" WHERE "book_tag"."book_id" IN (2)', [
    [
        'book_id' => 2,
        'tag_id' => 1,
    ],
    [
        'book_id' => 2,
        'tag_id' => 3,
    ],
]);

$driver->setResultData('SELECT "tag".* FROM "tag" WHERE "tag"."id" IN (1, 3)', [
    [
        'id' => 1,
        'name' => 'ebook',
    ],
    [
        'id' => 3,
        'name' => 'popular',
    ],
]);

$driver->setResultData('DELETE FROM "book_tag" WHERE "ctid" IN (SELECT "ctid" FROM "book_tag" WHERE "book_id" = 2 AND "tag_id" = 1 LIMIT 1)', []);

$driver->setResultData('DELETE FROM "book_tag" WHERE "ctid" IN (SELECT "ctid" FROM "book_tag" WHERE "book_id" = 2 AND "tag_id" = 3 LIMIT 1)', []);

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

$book = $bookRepository->find(2);

$sqls = array();
$connection->onEvent[] = function ($event) use (&$sqls) {
    $sqls[] = $event->sql;
};
$book->removeAllTags();
$bookRepository->persist($book);

Assert::equal([
    'SELECT "book_tag".* FROM "book_tag" WHERE "book_tag"."book_id" IN (2)',
    'SELECT "tag".* FROM "tag" WHERE "tag"."id" IN (1, 3)',
    'DELETE FROM "book_tag" WHERE "ctid" IN (SELECT "ctid" FROM "book_tag" WHERE "book_id" = 2 AND "tag_id" = 1 LIMIT 1)',
    'DELETE FROM "book_tag" WHERE "ctid" IN (SELECT "ctid" FROM "book_tag" WHERE "book_id" = 2 AND "tag_id" = 3 LIMIT 1)',
], $sqls);
