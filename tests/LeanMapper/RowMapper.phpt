<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

class Website
{
    private $url;


    public function __construct($url)
    {
        $this->url = $url;
    }


    public function getUrl()
    {
        return $this->url;
    }
}

/**
 * @property int $id
 * @property string $name
 * @property Website|NULL $web
 */
class Author extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Website|NULL $website
 * @property \DateTime $pubdate
 * @property Author $author m:hasOne
 */
class Book extends Entity
{
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

class CustomMapper extends TestMapper
{
    public function convertToRowData($table, array $values)
    {
        if ($table === 'book') {
            $values['website'] = $values['website'] !== null ? new Website($values['website']) : null;

        } elseif ($table === 'author') {
            $values['web'] = $values['web'] !== null ? new Website($values['web']) : null;
        }
        return $values;
    }


    public function convertFromRowData($table, array $data)
    {
        if ($table === 'book' && array_key_exists('website', $data)) {
            $data['website'] = $data['website'] instanceof Website ? $data['website']->getUrl() : $data['website'];

        } elseif ($table === 'author' && array_key_exists('web', $data)) {
            $data['web'] = $data['web'] instanceof Website ? $data['web']->getUrl() : $data['web'];
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
$book->name = 'test';
$book->author = $authorRepository->find(1);
$book->pubdate = new \DateTime;

Assert::exception(function () use ($book) {
    $book->website;
}, LeanMapper\Exception\Exception::class, 'Cannot get value of property \'website\' in entity Book due to low-level failure: Missing \'website\' column in row with id -1.');

$book->website = new Website('http://example.com');
Assert::same('http://example.com', $book->website->getUrl());

$bookRepository->persist($book);
Assert::same('http://example.com', $bookRepository->find($book->id)->website->getUrl());

$book->website = null;
Assert::null($book->website);

$bookRepository->persist($book);
Assert::null($bookRepository->find($book->id)->website);

$book->website = new Website('http://example2.com/path');
$bookRepository->persist($book);
Assert::same('http://example2.com/path', $bookRepository->find($book->id)->website->getUrl());

//// attached entity with NULL
$book = $bookRepository->find(2);
Assert::null($book->website);
Assert::null($book->author->web);
$book->name = 'Only modified name';
$bookRepository->persist($book);

//// attached entity with Website
$book = $bookRepository->find(3);
Assert::same('http://martinfowler.com/books/refactoring.html', $book->website->getUrl());
Assert::same('http://martinfowler.com', $book->author->web->getUrl());
