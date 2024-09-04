<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Initialize;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string|null $web
 * @property Book[] $books m:belongsToMany
 */
class Author extends Entity
{
    use Initialize;


    public function __construct(
        string $name,
        ?string $web
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->web = $web;
    }
}

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 * @property NULL|Author $author m:hasOne
 */
class Book extends Entity
{
    use Initialize;


    public function __construct(
        string $name,
        string $pubdate,
        ?Author $author
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->pubdate = $pubdate;
        $this->author = $author;
    }
}

class BookRepository extends \LeanMapper\Repository
{

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

Assert::type(Author::class, $book->author);

$author = $book->author;

$book->author = null;

Assert::equal(null, $book->author);

Assert::equal(
    [
        'author_id' => null,
    ],
    $book->getModifiedRowData()
);

$book->author = $author;

Assert::type(Author::class, $book->author);
