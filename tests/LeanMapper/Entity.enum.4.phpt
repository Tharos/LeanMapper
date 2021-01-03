<?php

declare(strict_types=1);

use PDO as DbLayer;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

interface BarInterface
{
    const STATE_NEW = 0;
    const STATE_CLOSED = 1;
}


/**
 * @property int $state m:enum(FooClass::STATE_*)
 */
class Author1 extends LeanMapper\Entity
{
}


/**
 * @property int $state m:enum(FooInterface::STATE_*)
 */
class Author2 extends LeanMapper\Entity
{
}


/**
 * @property int $state m:enum(BarInterface::STATE_*)
 */
class Author3 extends LeanMapper\Entity
{
}


//////////

Assert::exception(function () {

    $author = new Author1;

}, LeanMapper\Exception\InvalidStateException::class, "Class or interface FooClass not found.");


Assert::exception(function () {

    $author = new Author2;

}, LeanMapper\Exception\InvalidStateException::class, "Class or interface FooInterface not found.");



Assert::noError(function () {

    $author = new Author3;

});
