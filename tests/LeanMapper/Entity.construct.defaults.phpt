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
}, 'LeanMapper\Exception\Exception', "Cannot get value of property 'id' in entity Book due to low-level failure: missing 'id' column in row with id -1.");

Assert::exception(function () use ($book) {
	$book->getData();
}, 'LeanMapper\Exception\Exception', "Cannot get value of property 'id' in entity Book due to low-level failure: missing 'id' column in row with id -1.");