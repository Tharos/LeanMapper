<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class DebugRepository extends LeanMapper\Repository
{

	public function returnEntityClass()
	{
		return $this->getEntityClass();
	}

}

class ModuleRepository extends DebugRepository
{

	protected $defaultEntityNamespace = 'FrontModule';

}

class RootRepository extends DebugRepository
{

	protected $defaultEntityNamespace = null;

}

/**
 * @entity Book
 */
class BookRepository extends DebugRepository
{
}

/**
 * @entity \MyNamespace\Tag
 */
class TagRepository extends DebugRepository
{
}

class AuthorRepository extends DebugRepository
{
}

/**
 * @entity Author
 */
class MaintainerRepository extends ModuleRepository
{
}

/**
 * @entity \Author
 */
class Maintainer2Repository extends ModuleRepository
{
}

/**
 * @entity ExtraModule\SomeAuthor
 */
class Author2Repository extends RootRepository
{
}

$authorRepository = new AuthorRepository($connection);
$bookRepository = new BookRepository($connection);
$tagRepository = new TagRepository($connection);
$maintainerRepository = new MaintainerRepository($connection);
$maintainer2Repository = new Maintainer2Repository($connection);
$author2Repository = new Author2Repository($connection);

//////////

Assert::equal('Model\Entity\Author', $authorRepository->returnEntityClass());
Assert::equal('Model\Entity\Book', $bookRepository->returnEntityClass());
Assert::equal('MyNamespace\Tag', $tagRepository->returnEntityClass());
Assert::equal('FrontModule\Author', $maintainerRepository->returnEntityClass());
Assert::equal('Author', $maintainer2Repository->returnEntityClass());
Assert::equal('ExtraModule\SomeAuthor', $author2Repository->returnEntityClass());