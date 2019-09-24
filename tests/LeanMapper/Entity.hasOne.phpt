<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

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
 * @property Author|null $revieverId m:hasOne(reviewer_id)
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

Assert::exception(
    function () {
        $book = new Book();
        $book->revieverId;
    },
    'LeanMapper\Exception\InvalidStateException',
    'Cannot get value of property \'revieverId\' in entity Book due to low-level failure: Cannot get referenced Result for detached Result.'
);

$book = $bookRepository->find(1);
Assert::true($book->revieverId === null);
