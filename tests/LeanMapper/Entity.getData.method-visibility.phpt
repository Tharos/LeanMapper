<?php

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 */
class Author extends Entity
{

    public function getAuthorLabel()
    {
        return $this->name . ' (' . $this->getWebInfo() . ')';
    }



    protected function getWebInfo()
    {
        return isset($this->web) ? $this->web : 'none';
    }

}

class AuthorRepository extends \LeanMapper\Repository
{

    protected $defaultEntityNamespace = null;



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

Assert::equal(
    [
        'id' => 3,
        'name' => 'Martin Fowler',
        'web' => 'http://martinfowler.com',
        'authorLabel' => 'Martin Fowler (http://martinfowler.com)',
    ],
    $data
);
