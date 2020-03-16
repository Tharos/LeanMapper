<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:hasMany(#inversed)
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

////////////////////

Assert::exception(function () {
    $tag = new Tag;
    $tag->books;

}, LeanMapper\Exception\InvalidStateException::class, 'Cannot get value of property \'books\' in entity Tag due to low-level failure: Cannot get related Entities for detached Entity.');


Assert::exception(function () {
    $tag = new Tag;
    $tag->addToBooks(1);

}, LeanMapper\Exception\InvalidMethodCallException::class, 'Cannot add or remove related entity to detached entity.');


Assert::exception(function () {
    $book = new Book;
    $book->tags;

}, LeanMapper\Exception\InvalidStateException::class, 'Cannot get value of property \'tags\' in entity Book due to low-level failure: Cannot get related Entities for detached Entity.');


Assert::exception(function () {
    $book = new Book;
    $book->addToTags(1);

}, LeanMapper\Exception\InvalidMethodCallException::class, 'Cannot add or remove related entity to detached entity.');


Assert::exception(function () {
    $book = new Book;
    $book->getHasManyRowDifferences();

}, LeanMapper\Exception\InvalidStateException::class, 'Cannot get hasMany differences from detached entity.');


$book = new Book;
$book->makeAlive($entityFactory, $connection, $mapper);
$book->attach(1);
Assert::same([], $book->getHasManyRowDifferences());
