<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property non-empty-string $name
 * @property string|null $description
 * @property bool $available
 */
class Book extends LeanMapper\Entity
{
}


class BookRepository extends \LeanMapper\Repository
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

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);
$book = $bookRepository->find(1);

Assert::same('The Pragmatic Programmer', $book->name);
Assert::null($book->description);
Assert::true($book->available);
