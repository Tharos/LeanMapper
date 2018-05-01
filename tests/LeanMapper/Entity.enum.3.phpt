<?php

use PDO as DbLayer;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string|null $state m:enum(self::STATE_*)
 * @property string $finalState m:enum(self::STATE_*)
 */
class Author extends LeanMapper\Entity
{

    const STATE_ACTIVE = 'active';

    const STATE_INACTIVE = 'inactive';

    const STATE_DELETED = 'deleted';

}


//////////

$author = new Author;

$author->state = Author::STATE_ACTIVE;
Assert::equal(Author::STATE_ACTIVE, $author->state);

$author->state = NULL;
Assert::equal(NULL, $author->state);

$author->finalState = Author::STATE_DELETED;
Assert::equal(Author::STATE_DELETED, $author->finalState);

Assert::exception(function () use ($author) {

    $author->finalState = NULL;

}, 'LeanMapper\Exception\InvalidValueException', "Property 'finalState' in entity Author cannot be null.");
