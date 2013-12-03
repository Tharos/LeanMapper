<?php

use LeanMapper\Entity;
use LeanMapper\Result;
use LeanMapper\Row;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 */
class Book extends Entity
{

	protected function initDefaults()
	{
		$this->assign(array(
			'name' => 'Default name',
			'pubdate' => '2013-01-01 08:00:00',
		));
	}

}

//////////

$book = new Book;

Assert::type('Book', $book);

Assert::equal(array(
	'name' => 'Default name',
	'pubdate' => '2013-01-01 08:00:00',
), $book->getModifiedRowData());

Assert::equal(array(
	'name' => 'Default name',
	'pubdate' => '2013-01-01 08:00:00',
), $book->getRowData());

Assert::equal('Default name', $book->name);

Assert::exception(function () use ($book) {
	$book->id;
}, 'LeanMapper\Exception\InvalidArgumentException', "Missing row with id -1 or 'id' column in that row.");

Assert::exception(function () use ($book) {
	$book->getData();
}, 'LeanMapper\Exception\InvalidArgumentException', "Missing row with id -1 or 'id' column in that row.");