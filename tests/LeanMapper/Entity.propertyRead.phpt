<?php

use Tester\Assert;
use LeanMapper\Repository;
use LeanMapper\Entity;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property-read string $name
 */
class Author extends Entity
{
}

/**
 * @entity Author
 */
class AuthorRepository extends \LeanMapper\Repository
{

	protected $defaultEntityNamespace = null;


	public function find($id)
	{
		$row = $this->connection->select('*')->from($this->getTable())->where('id = %i', $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

$authorRepository = new AuthorRepository($connection);

//////////

$author = new Author;

Assert::exception(function () use ($author) {
	$author->name = 'test';
}, 'LeanMapper\Exception\MemberAccessException', "Cannot write to read only property 'name'.");

Assert::exception(function () use ($author) {
	$author->setName('test');
}, 'LeanMapper\Exception\MemberAccessException', "Cannot write to read only property 'name'.");

//////////

$author = $authorRepository->find(1);

Assert::exception(function () use ($author) {
	$author->name = 'test';
}, 'LeanMapper\Exception\MemberAccessException', "Cannot write to read only property 'name'.");

Assert::exception(function () use ($author) {
	$author->setName('test');
}, 'LeanMapper\Exception\MemberAccessException', "Cannot write to read only property 'name'.");