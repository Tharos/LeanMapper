<?php

use LeanMapper\DefaultMapper;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

$mapper = new DefaultMapper;

Assert::equal('id', $mapper->getPrimaryKey('author'));

Assert::equal('author', $mapper->getTable('Model\Entity\Author'));

Assert::equal('book', $mapper->getTable('Book'));

Assert::equal('Model\Entity\Author', $mapper->getEntityClass('author'));

Assert::equal('Model\Entity\Book', $mapper->getEntityClass('book'));

Assert::equal('firstName', $mapper->getColumn('Model\Entity\Author', 'firstName'));

Assert::equal('description', $mapper->getEntityField('book', 'description'));

Assert::equal('book_tag', $mapper->getRelationshipTable('book', 'tag'));

Assert::equal('author_id', $mapper->getRelationshipColumn('book', 'author'));

Assert::equal('reviewer_id', $mapper->getRelationshipColumn('book', 'author', 'reviewer'));

Assert::equal('author', $mapper->getTableByRepositoryClass('Model\Repository\AuthorRepository'));

//////////

$mapper = new DefaultMapper('App\Entities');

Assert::equal('App\Entities\Author', $mapper->getEntityClass('author'));

Assert::equal('App\Entities\Book', $mapper->getEntityClass('book'));

//////////

$mapper = new DefaultMapper(null);

Assert::equal('Author', $mapper->getEntityClass('author'));

Assert::equal('Book', $mapper->getEntityClass('book'));

//////////

class MyMapper extends DefaultMapper
{
    public function __construct(array $mapping)
    {
        parent::__construct();
        // some stuff
    }
}

$mapper = new MyMapper([]);

Assert::equal('Model\Entity\Author', $mapper->getEntityClass('author'));

Assert::equal('Model\Entity\Book', $mapper->getEntityClass('book'));

//////////

class MyMapper2 extends DefaultMapper
{
    public function __construct()
    {
        $this->defaultEntityNamespace = 'MyMapper2';
    }
}

$mapper = new MyMapper2();

Assert::equal('MyMapper2\Author', $mapper->getEntityClass('author'));

Assert::equal('MyMapper2\Book', $mapper->getEntityClass('book'));

//////////

// Old unsupported usage
//
// class MyMapper3 extends DefaultMapper
// {
//     protected $defaultEntityNamespace = 'MyMapper3';
// }

// $mapper = new MyMapper3();

// Assert::equal('MyMapper3\Author', $mapper->getEntityClass('author'));

// Assert::equal('MyMapper3\Book', $mapper->getEntityClass('book'));
