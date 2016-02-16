<?php

use LeanMapper\Entity;
use LeanMapper\Exception\Exception;
use LeanMapper\Result;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @property int $id
 * @property string $name
 */
class Author extends Entity
{
}

$author = new Author;

isset($author->name);

//////////

$author->makeAlive($entityFactory, $connection, $mapper);
$author->attach(1);

Assert::exception(function () use ($author) {
	isset($author->name);
}, 'LeanMapper\Exception\Exception', null, Result::ERROR_MISSING_COLUMN);
