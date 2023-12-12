<?php

declare(strict_types=1);

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

class AuthorRepository extends \LeanMapper\Repository
{
    /**
     * @param $id
     * @return Author
     * @throws Exception
     */
    public function find($id)
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($row);
    }
}

//////////

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$author = $authorRepository->find(1);
Assert::equal('Andrew Hunt', $author->name);
Assert::equal(null, $author->web);

$author = new Author();
Assert::equal(null, $author->web);
Assert::exception(
    function () {
        $author = new Author();
        $author->name;
    },
    LeanMapper\Exception\InvalidStateException::class,
    'Cannot get value of property \'name\' in entity Author due to low-level failure: Missing \'name\' column in row with id -1.'
);
