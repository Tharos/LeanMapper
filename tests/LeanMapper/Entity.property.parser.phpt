<?php

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property int $author (author_id)
 * @property int|null $reviewer(reviewer_id)
 * @property string $pubDate (pubdate) = 'foo1'     # default WILL NOT work
 * @property string $myName(name) = 'foo2'          # default WILL NOT work
 * @property string $description = 'foo3' (description)
 * @property string|null $web m:default("foo4") m:column(website) m:custom-flag(value)
 */
class Book extends Entity
{
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
Assert::equal(null, $book->web);

$entityReflection = $book->getReflection($mapper);
Assert::false($entityReflection->getEntityProperty('pubDate')->hasDefaultValue());
Assert::false($entityReflection->getEntityProperty('myName')->hasDefaultValue());
Assert::equal('foo3', $entityReflection->getEntityProperty('description')->getDefaultValue());
Assert::true($entityReflection->getEntityProperty('web')->hasDefaultValue());
Assert::equal('foo4', $entityReflection->getEntityProperty('web')->getDefaultValue());
Assert::true($entityReflection->getEntityProperty('web')->hasCustomFlag('custom-flag'));
Assert::equal('value', $entityReflection->getEntityProperty('web')->getCustomFlagValue('custom-flag'));

//////////

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books
 *     m:belongsToMany
 *     m:custom(custom value)
 *
 * @property string|null $website
 */
class Author extends Entity
{
}

class AuthorRepository extends \LeanMapper\Repository
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

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

//////////

/** @var Author $author */
$author = $authorRepository->find(2);
$books = $author->books;

Assert::equal(2, $author->id);
Assert::equal('Donald Knuth', $author->name);
Assert::equal(null, $author->website);
Assert::equal(1, count($books));

$book = reset($books);
Assert::equal(2, $book->id);

$entityReflection = $author->getReflection($mapper);
Assert::true($entityReflection->getEntityProperty('books')->hasCustomFlag('custom'));
Assert::equal('custom value', $entityReflection->getEntityProperty('books')->getCustomFlagValue('custom'));
