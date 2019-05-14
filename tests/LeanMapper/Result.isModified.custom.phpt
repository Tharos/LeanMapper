<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class CustomResult extends LeanMapper\Result
{
    public function setDataEntry($id, $key, $value)
    {
        if (!$this->hasDataEntry($id, $key) || $this->getDataEntry($id, $key) !== $value) {
            parent::setDataEntry($id, $key, $value);
        }
    }
}


class CustomRepository extends LeanMapper\Repository
{
    public function find($id)
    {
        $row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
        if ($row === false) {
            throw new \Exception('Entity was not found.');
        }
        return $this->createEntity($row);
    }


    protected function createEntity(Dibi\Row $dibiRow, $entityClass = null, $table = null)
    {
        if ($table === null) {
            $table = $this->getTable();
        }
        $result = CustomResult::createInstance($dibiRow, $table, $this->connection, $this->mapper);
        $primaryKey = $this->mapper->getPrimaryKey($table);

        $row = $result->getRow($dibiRow->$primaryKey);
        if ($entityClass === null) {
            $entityClass = $this->mapper->getEntityClass($table, $row);
        }
        $entity = $this->entityFactory->createEntity($entityClass, $row);
        $entity->makeAlive($this->entityFactory);
        return $entity;
    }


    // TODO: Repository::createEntities() && Entity::__construct()
}


/**
 * @property int $id
 * @property string $name
 */
class Author extends LeanMapper\Entity
{
}


/**
 * @property int $id
 * @property string $name
 * @property Author $author m:hasOne
 */
class Book extends LeanMapper\Entity
{
}


class AuthorRepository extends CustomRepository
{
}


class BookRepository extends CustomRepository
{
}


$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);
$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

//////////

$book = $bookRepository->find(3);

//////////

$oldName = $book->name;
$oldAuthor = $book->author;

$book->name = $oldName;
$book->author = $oldAuthor;

Assert::same(false, $book->isModified());

//////////

$book->name = 'new book name';
$book->author = $authorRepository->find(1);

Assert::same(true, $book->isModified());

//////////

$newBook = new Book;

Assert::same(false, $newBook->isModified());

$newBook->name = 'new book name #2';
$newBook->author = $authorRepository->find(1);

Assert::same(true, $newBook->isModified());
