<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

/**
 * @property int $id
 * @property string $name
 */
class Tag extends Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Tag[] $tags m:hasMany
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

function implodeTags(array $tags)
{
    $result = [];
    foreach ($tags as $tag) {
        $result[] = $tag->name;
    }
    return implode(',', $result);
}

////////////////////

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

$book = $bookRepository->find(2);

$book->addToTags(new ArrayObject([1, 2]));

Assert::equal('popular,ebook', implodeTags($book->tags));

$book->removeAllTags();

$book->addToTags(1);

Assert::equal('popular', implodeTags($book->tags));

$book->replaceAllTags([2, 1]);

Assert::equal('ebook,popular', implodeTags($book->tags));

$book->removeFromTags([1]);

Assert::equal('ebook', implodeTags($book->tags));

////////////////////

$book = $bookRepository->find(2);

$book->addToTags(1);

$bookRepository->persist($book);
$bookRepository->persist($book);

$book->addToTags(2);

$bookRepository->persist($book);
$bookRepository->persist($book);

Assert::equal([1, 2], $connection->query('SELECT [tag_id] FROM [book_tag] WHERE [book_id] = %i', 2)->fetchPairs());

////////////////////

$book = $bookRepository->find(2);

$book->addToTags(1);
$bookRepository->persist($book);

$book->addToTags(1);
$bookRepository->persist($book);

Assert::equal([1, 2], $connection->query('SELECT [tag_id] FROM [book_tag] WHERE [book_id] = %i', 2)->fetchPairs());

////////////////////

$book = $bookRepository->find(2);

$book->removeFromTags(1);
$bookRepository->persist($book);

$book->removeFromTags(1);
$bookRepository->persist($book);

Assert::equal([2], $connection->query('SELECT [tag_id] FROM [book_tag] WHERE [book_id] = %i', 2)->fetchPairs());
