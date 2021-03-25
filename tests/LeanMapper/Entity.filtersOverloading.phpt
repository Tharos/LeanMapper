<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Fluent;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection->onEvent[] = function ($event) use (&$queries, &$i) {
    $queries[] = $event->sql;
};

//////////

/**
 * @property int $id
 * @property Book[] $books m:belongsToMany m:filter(orderBy#id,orderBy#name)
 */
class Author extends Entity
{
}

/**
 * @property int $id
 */
class Book extends Entity
{
}

class AuthorRepository extends LeanMapper\Repository
{

    public function find($id)
    {
        $entry = $this->createFluent()->where('[id] = %i', $id)->fetch();
        if ($entry === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($entry);
    }

}

////////////////////
////////////////////

$connection->registerFilter(
    'orderBy',
    function (Fluent $statement, $orderBy) {
        $statement->orderBy($orderBy);
    }
);

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$authorRepository->find(1)->books;

Assert::equal('SELECT [book].* FROM [book] WHERE [book].[author_id] IN (1) ORDER BY [name]', $queries[1]);
