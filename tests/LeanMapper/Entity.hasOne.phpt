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

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

Assert::exception(
    function () {
        $book = new Book();
        $book->revieverId;
    },
    LeanMapper\Exception\InvalidStateException::class,
    'Cannot get value of property \'revieverId\' in entity Book due to low-level failure: Cannot get referenced Entity for detached Entity.'
);

$book = $bookRepository->find(1);
Assert::true($book->revieverId === null);
