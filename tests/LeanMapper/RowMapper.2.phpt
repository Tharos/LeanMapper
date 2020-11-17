<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:belongsToMany
 */
class Author extends Entity
{
}

/**
 * @property int $id
 * @property BookInfo $info
 * @property \DateTime $pubdate
 * @property Author $author m:hasOne
 */
class Book extends Entity
{
}

class BookInfo
{
    private $name;
    private $description;


    public function  __construct(string $name, ?string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }


    public function getName(): string
    {
        return $this->name;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }
}

class AuthorRepository extends \LeanMapper\Repository
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

class CustomMapper extends \LeanMapper\DefaultMapper
{
    public function convertToRowData(string $table, array $values): array
    {
        if ($table === 'book') {
            $values['info'] = new BookInfo($values['name'], $values['description']);
            unset($values['name']);
            unset($values['description']);
        }
        return $values;
    }


    public function convertFromRowData(string $table, array $data): array
    {
        if ($table === 'book' && array_key_exists('info', $data)) {
            $data['name'] = $data['info']->getName();
            $data['description'] = $data['info']->getDescription();
            unset($data['info']);
        }
        return $data;
    }
}

////////////////////

$mapper = new CustomMapper(null);
$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

//// detached entity
$book = new Book;
$book->author = $authorRepository->find(1);
$book->pubdate = new \DateTime;

Assert::exception(function () use ($book) {
    $book->info;
}, LeanMapper\Exception\Exception::class, 'Cannot get value of property \'info\' in entity Book due to low-level failure: Missing \'info\' column in row with id -1.');

$book->info = new BookInfo('Name', 'description');
Assert::same('Name', $book->info->getName());
Assert::same('description', $book->info->getDescription());

$bookRepository->persist($book);
$bookInfo = $bookRepository->find($book->id)->info;
Assert::same('Name', $bookInfo->getName());
Assert::same('description', $bookInfo->getDescription());

$book->info = new BookInfo('Name 2', null);
$bookRepository->persist($book);
$bookInfo = $bookRepository->find($book->id)->info;
Assert::same('Name 2', $bookInfo->getName());
Assert::null($bookInfo->getDescription());

//// attached entity with NULL
$book = $bookRepository->find(1);
Assert::same($book->info, $book->info);
Assert::same('The Pragmatic Programmer', $book->info->getName());
Assert::null($book->info->getDescription());

//// attached entity with BookInfo
$book = $bookRepository->find(2);
Assert::same('The Art of Computer Programming', $book->info->getName());
Assert::same('very old book about programming', $book->info->getDescription());
$book->pubdate = new \DateTime;
$bookRepository->persist($book);
