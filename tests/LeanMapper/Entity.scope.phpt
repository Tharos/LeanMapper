<?php

use Tester\Assert;
use LeanMapper\Repository;
use LeanMapper\Entity;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name m:useMethods(readName|assignName)
 * @property string|null $web
 */
class Author extends Entity
{

	public function readName()
	{
		return $this->get('name');
	}

	public function assignName($name)
	{
		$this->set('name', $name);
	}

	public function getWeb()
	{
		return $this->get('web');
	}

}

class AuthorRepository extends Repository
{

	protected $defaultEntityNamespace = null;


	public function find($id)
	{
		$row = $this->createFluent()->where('id = %i', $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

}

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);

//////////

$author = $authorRepository->find(3);

Assert::equal('Martin Fowler', $author->name);

$author->name = 'John Doe';

Assert::equal('John Doe', $author->name);

Assert::equal('http://martinfowler.com', $author->web);

$author->web = 'http://www.leanmapper.com';

Assert::equal('http://www.leanmapper.com', $author->web);
