<?php

use LeanMapper\Connection;
use LeanMapper\DefaultEntityFactory;
use LeanMapper\DefaultMapper;
use LeanMapper\Entity;
use LeanMapper\Repository;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

if (!copy(__DIR__ . '/../db/author-ref.sq3', __DIR__ . '/../db/author.sq3')) {
	echo 'Failed to copy SQLite database';
	exit(1);
}

//////////

/**
 * @property int $id
 * @property AuthorDetail $authorDetail m:belongsToOne
 * @property string $name
 */
class Author extends Entity
{
}

/**
 * @property Author $author m:hasOne
 * @property AuthorContract $authorContract m:belongsToOne
 * @property string $address
 */
class AuthorDetail extends Entity
{
}

/**
 * @property AuthorDetail $authorDetail m:hasOne
 * @property string $number
 */
class AuthorContract extends Entity
{
}


class Mapper extends DefaultMapper
{

	protected $defaultEntityNamespace = null;


	public function getPrimaryKey($table)
	{
		if ($table === 'authordetail' or $table === 'authorcontract') {
			return 'author_id';
		}
		return parent::getPrimaryKey($table);
	}

	public function getColumn($entityClass, $field)
	{
		if ($entityClass === 'AuthorDetail' and $field === 'author') {
			return 'author_id';
		}
		if ($entityClass === 'AuthorContract' and $field === 'authorContract') {
			return 'author_id';
		}
		return parent::getColumn($entityClass, $field);
	}

	public function getRelationshipColumn($sourceTable, $targetTable)
	{
		if ($sourceTable === 'authorcontract' and $targetTable === 'authordetail') {
			return 'author_id';
		}
		if ($sourceTable === 'authordetail' and $targetTable === 'author') {
			return 'author_id';
		}
		return parent::getRelationshipColumn($sourceTable, $targetTable);
	}

	public function getEntityField($table, $column)
	{
		if ($table === 'authordetail' and $column === 'author_id') {
			return 'author';
		}
		if ($table === 'authorcontract' and $column === 'author_id') {
			return 'authorDetail';
		}
		return parent::getEntityField($table, $column);
	}

}

abstract class BaseRepository extends Repository
{

	public function find($id)
	{
		$row = $this->createFluent()->where('%n = %i', $this->mapper->getPrimaryKey($this->getTable()), $id)->fetch();
		if ($row === false) {
			throw new \Exception('Entity was not found.');
		}
		return $this->createEntity($row);
	}

	public function findAll()
	{
		return $this->createEntities(
			$this->createFluent()->fetchAll()
		);
	}

}

class AuthorRepository extends BaseRepository
{
}

class AuthorContractRepository extends BaseRepository
{
}

//////////

$dbConfig = array(
	'driver' => 'sqlite3',
	'database' => __DIR__ . '/../db/author.sq3',
);

$connection = new Connection($dbConfig);

$connection->onEvent[] = function ($event) use (&$queries) {
	$queries[] = $event->sql;
};

$mapper = new Mapper;
$entityFactory = new DefaultEntityFactory;

$authorRepository = new AuthorRepository($connection, $mapper, $entityFactory);;
$authorContractRepository = new AuthorContractRepository($connection, $mapper, $entityFactory);;

//////////

$authorContract = $authorContractRepository->find(1);

Assert::equal('ABC1234', $authorContract->number);
Assert::equal('New York', $authorContract->authorDetail->address);
Assert::equal('John Doe', $authorContract->authorDetail->author->name);

$author = $authorRepository->find(1);

Assert::equal('John Doe', $author->name);
Assert::equal('New York', $author->authorDetail->address);
Assert::equal('ABC1234', $author->authorDetail->authorContract->number);

$queries = array();

$authorContract->number = '12345';
$authorContractRepository->persist($authorContract);

$author = $authorContract->authorDetail->author;
$author->name = 'Vojtěch Kohout';

$authorRepository->persist($author);

Assert::equal(array(
	"UPDATE [authorcontract] SET [number]='12345' WHERE [author_id] = 1",
	"UPDATE [author] SET [name]='Vojtěch Kohout' WHERE [id] = 1",
), $queries);
