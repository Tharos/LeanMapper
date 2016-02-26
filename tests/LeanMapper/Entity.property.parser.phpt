<?php

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property int $author (author_id)
 * @property int|null $reviewer(reviewer_id)
 * @property string $pubDate (pubdate) = 'foo'
 * @property string $myName(name) = 'foo'
 * @property string $description = 'foo' (description)
 */
class Book extends Entity
{
}

class BookRepository extends \LeanMapper\Repository
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

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

//////////

/** @var Book $book */
$book = $bookRepository->find(2);

Assert::equal(2, $book->id);
Assert::equal(2, $book->author);
Assert::equal(1, $book->reviewer);
Assert::equal('1968-04-08', $book->pubDate);
Assert::equal('The Art of Computer Programming', $book->myName);
Assert::equal('very old book about programming', $book->description);
