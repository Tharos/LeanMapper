<?php

use LeanMapper\Reflection\Property;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property int $id
 * @property string $name
 * @property DateTime $pubdate
 * @property Time $time
 * @property Uuid $uuid
 * @property Integer[] $numbers
 */
class Book extends LeanMapper\Entity
{
    protected function decodeRowValue($value, Property $property)
    {
        $type = $property->getType();

        if ($type === 'array') {
            return $this->convertJson($value);
        }

        if (is_a($type, 'DateTime', true)) {
            if ($value instanceof DateTime) {
                return clone $value;
            }
            return new DateTime($value);
        }

        if (is_a($type, 'Uuid', true)) {
            return ($value instanceof Uuid) ? $value : new Uuid($value);
        }

        if (is_a($type, 'Time', true)) {
            return ($value instanceof Time) ? $value->getTime() : new Time($value);
        }

        if (!$property->isBasicType() && is_a($type, 'Integer', true)) { // is_a() accepts 'integer' as class name
            return $this->convertToInteger($value);
        }

        return parent::decodeRowValue($value, $property);
    }


    protected function encodeRowValue($value, Property $property)
    {
        $type = $property->getType();

        if ($type === 'array') {
            return $this->convertJson($value);
        }

        if (is_a($type, 'DateTime', true)) {
            if ($value instanceof DateTime) {
                return $value->format('Y-m-d');
            }
            return $value;
        }

        if (is_a($type, 'Uuid', true)) {
            return ($value instanceof Uuid) ? $value : new Uuid($value);
        }

        if (is_a($type, 'Time', true)) {
            return ($value instanceof Time) ? $value->getTime() : new Time($value);
        }

        if (!$property->isBasicType() && is_a($type, 'Integer', true)) { // is_a() accepts 'integer' as class name
            return $this->convertToInteger($value);
        }

        return parent::encodeRowValue($value, $property);
    }


    protected function convertToInteger($value)
    {
        if (is_string($value)) {
            $res = [];
            $numbers = explode(',', $value);

            foreach ($numbers as $number) {
                $res[] = new Integer(trim($number));
            }

            return $res;
        }

        if ($value instanceof Integer) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(',', $value);
        }
    }
}


class Uuid
{
    private $uuid;


    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }


    public function getUuid()
    {
        return $this->uuid;
    }
}


class Time
{
    private $hour;
    private $minute;


    public function __construct($time)
    {
        $parts = explode(':', $time);
        $this->hour = (int) $parts[0];
        $this->minute = (int) $parts[1];
    }


    public function getHour()
    {
        return $this->hour;
    }


    public function getMinute()
    {
        return $this->minute;
    }


    public function getTime()
    {
        return str_pad($this->hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($this->minute, 2, '0', STR_PAD_LEFT);
    }
}

class Integer
{
    private $value;


    public function __construct($value)
    {
        $this->value = (int) $value;
    }


    public function getValue()
    {
        return $this->value;
    }


    public function __toString()
    {
        return (string) $this->value;
    }
}

//////////

$booksResult = LeanMapper\Result::createInstance([
    [
        'id' => 1,
        'name' => 'First book',
        'pubdate' => '1999-10-30',
        'time' => '12:55',
        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        'numbers' => '2,4,8,16',
    ],
], 'book', $connection, $mapper);

$book = new Book($booksResult->getRow(1));
$book->makeAlive($entityFactory, $connection, $mapper);

// get
Assert::same(1, $book->id);
Assert::same('First book', $book->name);
Assert::same('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', $book->uuid->getUuid());
Assert::same('30.10.1999', $book->pubdate->format('d.m.Y'));
Assert::same('12:55', $book->time->getTime());

$values = [];

foreach ($book->numbers as $number) {
    $values[] = $number->getValue();
}

Assert::same([2, 4, 8, 16], $values);

// set
$book->name = 'My book';
Assert::same('My book', $book->name);

$book->time = new Time('8:44');
$book->numbers = [
    new Integer(2),
    new Integer(32),
];
Assert::same([
    'id' => 1,
    'name' => 'My book',
    'pubdate' => '1999-10-30',
    'time' => '08:44',
    'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
    'numbers' => '2,32',
], $book->getRowData());
