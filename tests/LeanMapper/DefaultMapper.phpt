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

Assert::equal('author', $mapper->getTableByRepositoryClass('Model\Repository\AuthorRepository'));