<?php

declare(strict_types=1);

use LeanMapper\DefaultEntityFactory;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class CustomEntityFactory extends DefaultEntityFactory
{

    public function createCollection(array $entites)
    {
        return new ArrayObject($entites);
    }

}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 * @property Author $author m:hasOne
 */
class Book extends LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 * @property Book[] $books m:belongsToMany
 */
class Author extends LeanMapper\Entity
{
}

class AuthorRepository extends LeanMapper\Repository
{

    public function find($id)
    {
        $primaryKey = $this->mapper->getPrimaryKey($this->getTable());
        return $this->createEntity(
            $this->createFluent()->where('%n = %i', $primaryKey, $id)->fetch()
        );
    }



    public function findAll()
    {
        return $this->createEntities(
            $this->createFluent()->fetchAll()
        );
    }

}

//////////

$authorRepository = new AuthorRepository($connection, $mapper, new CustomEntityFactory);

$author = $authorRepository->find(1);

$books = $author->books;

Assert::type('ArrayObject', $books);

Assert::equal(1, count($books));

//////////

$authors = $authorRepository->findAll();

Assert::type('ArrayObject', $authors);

Assert::equal(5, count($authors));
