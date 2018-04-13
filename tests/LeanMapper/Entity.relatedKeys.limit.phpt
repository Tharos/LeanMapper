<?php

use LeanMapper\Connection;
use LeanMapper\Entity;
use LeanMapper\Fluent;
use LeanMapper\Reflection\Property;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection->onEvent[] = function ($event) use (&$queries, &$i) {
    $queries[] = $event->sql;
};

//////////

/**
 * @property int $id
 * @property Book[] $oldestBook m:belongsToMany(#union) m:filter(limit#1,orderBy#pubdate)
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

    public function findAll()
    {
        return $this->createEntities(
            $this->createFluent()->fetchAll()
        );
    }

}

////////////////////
////////////////////

$connection->registerFilter(
    'limit',
    function (Fluent $statement, $limit) {
        $statement->limit($limit);
    }
);

$connection->registerFilter(
    'orderBy',
    function (Fluent $statement, $column) {
        $statement->orderBy('%n', $column);
    }
);

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$authors = $authorRepository->findAll();
$author = reset($authors);

$author->oldestBook;

Assert::equal(
    [
        'SELECT [author].* FROM [author]',
        'SELECT * FROM (' . implode(') UNION SELECT * FROM (', array(
            'SELECT [book].* FROM [book] WHERE [book].[author_id] = 1 ORDER BY [pubdate] LIMIT 1',
            'SELECT [book].* FROM [book] WHERE [book].[author_id] = 2 ORDER BY [pubdate] LIMIT 1',
            'SELECT [book].* FROM [book] WHERE [book].[author_id] = 3 ORDER BY [pubdate] LIMIT 1',
            'SELECT [book].* FROM [book] WHERE [book].[author_id] = 4 ORDER BY [pubdate] LIMIT 1',
            'SELECT [book].* FROM [book] WHERE [book].[author_id] = 5 ORDER BY [pubdate] LIMIT 1',
        )) . ')'
    ],
    $queries
);
