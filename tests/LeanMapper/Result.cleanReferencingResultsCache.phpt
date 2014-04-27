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

	public function refreshTags()
	{
		$this->row->cleanReferencingRowsCache('book_tag', 'book_id');
	}

}

class BookRepository extends \LeanMapper\Repository
{

	/**
	 * @param int $id
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

function implodeTags(array $tags) {
	$result = array();
	foreach ($tags as $tag) {
		$result[] = $tag->name;
	}
	return implode(',', $result);
}

////////////////////

$bookRepository = new BookRepository($connection, $mapper, $entityFactory);

$book = $bookRepository->find(1);

Assert::equal('popular,ebook', implodeTags($book->tags));

$connection->query('DELETE FROM [book_tag] WHERE [book_id] = 1 AND [tag_id] = 1');

Assert::equal('popular,ebook', implodeTags($book->tags));

$book->refreshTags();

Assert::equal('ebook', implodeTags($book->tags));
