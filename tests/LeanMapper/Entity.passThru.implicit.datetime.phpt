<?php

declare(strict_types=1);

use LeanMapper\Reflection\Property;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property DateTimeInterface $pubdate
 */
class Book extends LeanMapper\Entity
{
    protected function decodeRowValue($value, Property $property)
    {
        if (is_a($property->getType(), DateTimeInterface::class, true)) {
            if ($value instanceof DateTimeInterface) {
                return clone $value;
            }
            return new DateTime($value);
        }

        return parent::decodeRowValue($value, $property);
    }


    protected function encodeRowValue($value, Property $property)
    {
        if (is_a($property->getType(), DateTimeInterface::class, true)) {
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d');
            }
            return $value;
        }

        return parent::encodeRowValue($value, $property);
    }
}

//////////

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

$booksResult = LeanMapper\Result::createInstance([
    [
        'id' => '1',
        'pubdate' => '1999-10-30',
    ],

    [
        'id' => '2',
        'pubdate' => new \Dibi\DateTime('2000-10-30'),
    ],
], 'book', $connection, $mapper);

// datetime as string
$book = new Book($booksResult->getRow(1));
$book->makeAlive($entityFactory, $connection, $mapper);

$rowData = $book->getRowData();
Assert::same('1999-10-30', $rowData['pubdate']);
Assert::same('1999-10-30', $book->pubdate->format('Y-m-d'));


// datetime as object
$book = new Book($booksResult->getRow(2));
$book->makeAlive($entityFactory, $connection, $mapper);

$rowData = $book->getRowData();
Assert::true($rowData['pubdate'] instanceof Dibi\DateTime);
Assert::notSame($rowData['pubdate'], $book->pubdate);
Assert::same('2000-10-30', $book->pubdate->format('Y-m-d'));

$book->pubdate = new DateTime('2001-10-30');
$rowData = $book->getRowData();
Assert::same('2001-10-30', $rowData['pubdate']);
