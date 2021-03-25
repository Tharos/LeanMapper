<?php

declare(strict_types=1);

use LeanMapper\Reflection\Property;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $implicit
 * @property string $explicit m:passThru(read|write)
 */
class Book extends LeanMapper\Entity
{
    protected function decodeRowValue($value, Property $property)
    {
        if ($property->getType() === 'string') {
            return strrev($value);
        }

        return parent::decodeRowValue($value, $property);
    }


    protected function encodeRowValue($value, Property $property)
    {
        if ($property->getType() === 'string') {
            return strrev($value);
        }

        return parent::encodeRowValue($value, $property);
    }


    protected function read($value)
    {
        return strtolower($value);
    }


    protected function write($value)
    {
        return strtoupper($value);
    }
}

//////////

$book = new Book;

$book->implicit = 'abc';
$book->explicit = 'xyz';

Assert::same([
    'implicit' => 'cba',
    'explicit' => 'ZYX',
], $book->getRowData());

Assert::same('abc', $book->implicit);
Assert::same('xyz', $book->explicit);
