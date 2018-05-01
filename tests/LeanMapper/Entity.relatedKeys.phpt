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
 * @property Book[] $books m:belongsToMany m:filter(test)
 * @property Book[] $unionBooks m:belongsToMany(#union) m:filter(test)
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
    'test',
    function (Fluent $statement, Property $property) {
        $ids = $statement->getRelatedKeys();
        $statement->removeClause('where');
        if ($property->getName() === 'books') {
            $statement->where('%n.%n IN %in', 'book', 'author_id', $ids);
        } else {
            $statement->where('%n.%n = %i', 'book', 'author_id', reset($ids));
        }
    },
    Connection::WIRE_PROPERTY
);

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$authors = $authorRepository->findAll();
$author = reset($authors);

$author->books;
$author->unionBooks;

Assert::equal(
    [
        'SELECT [author].* FROM [author]',
        'SELECT [book].* FROM [book] WHERE [book].[author_id] IN (1, 2, 3, 4, 5)',
        'SELECT * FROM (SELECT [book].* FROM [book] WHERE [book].[author_id] = 1) UNION SELECT * FROM (SELECT [book].* FROM [book] WHERE [book].[author_id] = 2) UNION SELECT * FROM (SELECT [book].* FROM [book] WHERE [book].[author_id] = 3) UNION SELECT * FROM (SELECT [book].* FROM [book] WHERE [book].[author_id] = 4) UNION SELECT * FROM (SELECT [book].* FROM [book] WHERE [book].[author_id] = 5)',
    ],
    $queries
);
