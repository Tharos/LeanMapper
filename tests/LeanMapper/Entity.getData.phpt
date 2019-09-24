<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 * @property Book[] $books m:belongsToMany
 */
class Author extends Entity
{

    public function getUpperName()
    {
        return strtoupper($this->name);
    }



    public function getShortName($length)
    {
        return substr($this->name, 0, $length);
    }

}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 */
class Book extends Entity
{
}

class AuthorRepository extends \LeanMapper\Repository
{

    public function find($id)
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($row);
    }

}

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

//////////

$author = $authorRepository->find(3);

$data = $author->getData();

Assert::equal(['id', 'name', 'web', 'books', 'upperName'], array_keys($data));

$reducedData = array_intersect_key($data, array_flip(['id', 'name', 'web', 'upperName']));

Assert::equal(
    [
        'id' => 3,
        'name' => 'Martin Fowler',
        'web' => 'http://martinfowler.com',
        'upperName' => 'MARTIN FOWLER',
    ],
    $reducedData
);

foreach ($data['books'] as $book) {
    Assert::type('Book', $book);
}

Assert::equal(2, count($data['books']));
