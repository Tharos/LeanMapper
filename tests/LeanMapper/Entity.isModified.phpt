<?php

use LeanMapper\Entity;
use LeanMapper\Exception\Exception;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @property int $id
 * @property string $name
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

$author = $authorRepository->find(3);

//////////

$oldName = $author->name;

$author->name = $oldName;

Assert::equal(FALSE, $author->isModified());

//////////

$author->name = 'new author name';

Assert::equal(TRUE, $author->isModified());
