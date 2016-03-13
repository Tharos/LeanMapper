<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 */
class Author extends LeanMapper\Entity
{
}

class AuthorRepository extends LeanMapper\Repository
{

    protected $defaultEntityNamespace = null;



    public function find($id)
    {
        $entry = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)
            ->fetch();

        if ($entry === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($entry);
    }

}

//////////

$author = new Author;

Assert::equal([], $author->getRowData());

$author->name = 'John Doe';
$author->web = null;

Assert::equal(['name' => 'John Doe', 'web' => null], $author->getRowData());

$author->web = 'http://example.org';

Assert::equal(['name' => 'John Doe', 'web' => 'http://example.org'], $author->getRowData());

//////////

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

$author = $authorRepository->find(3);

Assert::equal(
    [
        'id' => 3,
        'name' => 'Martin Fowler',
        'web' => 'http://martinfowler.com',
    ],
    $author->getRowData()
);

$author->web = null;

Assert::equal(
    [
        'id' => 3,
        'name' => 'Martin Fowler',
        'web' => null,
    ],
    $author->getRowData()
);
