<?php

declare(strict_types=1);

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

class Mapper extends DefaultMapper
{

    protected $defaultEntityNamespace = null;

}

/**
 * @property int $id
 * @property string $name
 */
class Author extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Author|null $reviewer m:hasOne
 */
class Book extends Entity
{
}

class BookRepository extends \LeanMapper\Repository
{
    /**
     * @param $id
     * @return Book
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

////////////////////

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);
$book = $bookRepository->find(3);
Assert::same(4, $book->reviewer->id);
Assert::same('Kent Beck', $book->reviewer->name);

$book = $bookRepository->find(1);
Assert::null($book->reviewer);
