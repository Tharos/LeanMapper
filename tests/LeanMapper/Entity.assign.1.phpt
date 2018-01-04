<?php

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class Mapper extends DefaultMapper
{

    public function getPrimaryKey($table)
    {
        if ($table === 'author') {
            return 'customid';
        }
        return parent::getPrimaryKey($table);
    }



    public function getEntityField($table, $column)
    {
        if ($table === 'author' and $column === $this->getPrimaryKey($table)) {
            return 'customid';
        }
        return parent::getEntityField($table, $column);
    }

}

/**
 * @property string $name
 */
class BaseEntity extends Entity
{
}

/**
 * @property int $customid
 */
class Author extends BaseEntity
{
}

/**
 * @property int $id
 * @property Author $author m:hasOne
 */
class Book extends BaseEntity
{
}

//////////

$mapper = new Mapper;

$author = new Author;
$author->name = 'John Doe';
$author->makeAlive($entityFactory, $connection, $mapper);
$author->attach(1);

Assert::equal(
    [
        'customid' => 1,
        'name' => 'John Doe',
    ],
    $author->getData()
);

$book = new Book;
$book->author = $author;

Assert::equal(['author_customid' => 1], $book->getModifiedRowData());
Assert::equal(['author_customid' => 1], $book->getRowData());
