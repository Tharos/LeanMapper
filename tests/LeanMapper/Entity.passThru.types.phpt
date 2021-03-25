<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property SomeType $value
 * @property SomeType $valuePass m:passThru(read|write)
 */
class Book extends LeanMapper\Entity
{
    protected function read($value)
    {
        return new SomeType($value);
    }


    protected function write(SomeType $value)
    {
        return $value->getValue();
    }
}


class SomeType
{
    private $value;


    public function __construct($value)
    {
        $this->value = $value;
    }


    public function getValue()
    {
        return $this->value;
    }
}

//////////

$book = new Book;
$value = new SomeType('ABC');

// set
$book->value = $value;
$book->valuePass = $value;

Assert::same([
    'value' => $value,
    'valuePass' => 'ABC',
], $book->getRowData());

// get
Assert::same($value, $book->value);
$valuePass = $book->valuePass;
Assert::notSame($value, $valuePass);
Assert::equal($value, $valuePass);
