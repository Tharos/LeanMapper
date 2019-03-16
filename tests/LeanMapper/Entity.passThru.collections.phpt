<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property \DateTime[] $pubdates
 * @property \DateTime[] $pubdatesPass m:passThru(read|write)
 */
class Book extends LeanMapper\Entity
{
    protected function read($values)
    {
        return array_reverse($values);
    }


    protected function write($values)
    {
        return array_reverse($values);
    }
}

//////////

$book = new Book;
$dateA = new DateTime('2018-01-01 00:00:00');
$dateB = new DateTime('2018-12-31 00:00:00');

// set
$book->pubdates = [$dateA, $dateB];
$book->pubdatesPass = [$dateA, $dateB];

Assert::same([
    'pubdates' => [$dateA, $dateB],
    'pubdatesPass' => [$dateB, $dateA],
], $book->getRowData());

// get
Assert::same([$dateA, $dateB], $book->pubdates);
Assert::same([$dateA, $dateB], $book->pubdatesPass);
