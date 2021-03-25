<?php

declare(strict_types=1);

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property Book[] $books m:belongsToMany
 * @property Book $book m:belongsToOne
 */
class Author extends Entity
{
}


class Book extends Entity
{
}

//////////

Assert::exception(function () {
    $author = new Author;
    $author->books;

}, LeanMapper\Exception\InvalidStateException::class, 'Cannot get value of property \'books\' in entity Author due to low-level failure: Cannot get related Entities for detached Entity.');


Assert::exception(function () {
    $author = new Author;
    $author->book;

}, LeanMapper\Exception\InvalidStateException::class, 'Cannot get value of property \'book\' in entity Author due to low-level failure: Cannot get referenced Entity for detached Entity.');
