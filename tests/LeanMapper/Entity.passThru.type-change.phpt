<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name m:passThru(|serialize)
 * @property \DateTime $pubdate m:passThru(convertDate|)
 */
class Book extends LeanMapper\Entity
{
    public function serialize($name)
    {
        return strtolower(implode($name));
    }


    protected function convertDate($date)
    {
        return is_string($date) ? new DateTime($date) : $date;
    }
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

//////////

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);
$book = $bookRepository->find(1);

// set
$book->name = ['John Doe'];
Assert::same('john doe', $book->name);

// get
Assert::same('1999-10-30', $book->pubdate->format('Y-m-d'));
