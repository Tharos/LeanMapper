<?php

use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

class Mapper extends DefaultMapper
{

	protected $defaultEntityNamespace = null;

}

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

/**
 * @entity Book
 */
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

function implodeTags(array $tags) {
	$result = array();
	foreach ($tags as $tag) {
		$result[] = $tag->name;
	}
	return implode(',', $result);
}

////////////////////

$bookRepository = new BookRepository($connection, new Mapper);

$book = $bookRepository->find(2);

$book->addToTags(new ArrayObject(array(1, 2)));

Assert::equal('popular,ebook', implodeTags($book->tags));

$book->removeAllTags();

$book->addToTags(1);

Assert::equal('popular', implodeTags($book->tags));

$book->replaceAllTags(array(2, 1));

Assert::equal('ebook,popular', implodeTags($book->tags));

$book->removeFromTags(array(1));

Assert::equal('ebook', implodeTags($book->tags));