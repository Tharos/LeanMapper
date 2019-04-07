<?php

use LeanMapper\Reflection\Property;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property array $meta
 */
class Book extends LeanMapper\Entity
{
    protected function decodeRowValue($value, Property $property)
    {
        if ($property->getType() === 'array') {
            return $this->convertJson($value);
        }

        return parent::decodeRowValue($value, $property);
    }


    protected function encodeRowValue($value, Property $property)
    {
        if ($property->getType() === 'array') {
            return $this->convertJson($value);
        }

        return parent::encodeRowValue($value, $property);
    }


    protected function convertJson($value)
    {
        if (is_string($value)) {
            return (array) json_decode($value, true);
        }

        return json_encode((array) $value);
    }
}

//////////

$booksResult = LeanMapper\Result::createInstance([
    [
        'id' => 1,
        'meta' => '{"rating":10}',
    ],
], 'book', $connection, $mapper);

$book = new Book($booksResult->getRow(1));
$book->makeAlive($entityFactory, $connection, $mapper);

// getter
Assert::same([
    'rating' => 10,
], $book->meta);

// setter
$meta = $book->meta;
$meta['signature'] = 'B';
$book->meta = $meta;
Assert::same([
    'id' => 1,
    'meta' => '{"rating":10,"signature":"B"}',
], $book->getRowData());
