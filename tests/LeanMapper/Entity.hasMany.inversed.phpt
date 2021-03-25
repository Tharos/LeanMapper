<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:hasMany(#inversed)
 */
class Tag extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:hasMany(:book_tag#inversed)
 */
class BrokenTag extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:belongsToMany(#inversed)
 */
class BrokenAuthor extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 */
class Book extends Entity
{
}

class TagRepository extends \LeanMapper\Repository
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

function fetchBooksId(array $books)
{
    $result = [];
    foreach ($books as $book) {
        $result[] = $book->id;
    }
    return $result;
}

////////////////////

$tagRepository = new TagRepository($connection, $mapper, $entityFactory);

$tag = $tagRepository->find(2);

Assert::equal([1, 3], fetchBooksId($tag->books));

////////////////////

Assert::exception(function () use ($mapper) {
    $reflection = BrokenTag::getReflection($mapper);
    $reflection->getEntityProperties();
}, LeanMapper\Exception\InvalidAnnotationException::class, 'It doesn\'t make sense to combine #inversed and hardcoded relationship table in entity BrokenTag.');

Assert::exception(function () use ($mapper) {
    $reflection = BrokenAuthor::getReflection($mapper);
    $reflection->getEntityProperties();
}, LeanMapper\Exception\InvalidAnnotationException::class, 'It doesn\'t make sense to have #inversed in belongsToMany relationship in entity BrokenAuthor.');
