<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Initialize;
use LeanMapper\Result;
use LeanMapper\Row;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$connection = Tests::createConnection();
$mapper = Tests::createMapper();
$entityFactory = Tests::createEntityFactory();

//////////

/**
 * @property int $id
 * @property string $name
 * @property string $pubdate
 */
class Book extends Entity
{
    use Initialize;


    public function __construct(
        string $name,
        string $pubdate
    )
    {
        parent::__construct();

        $this->name = $name;
        $this->pubdate = $pubdate;
    }
}

//////////

$book = Book::initialize([]);

Assert::type(Book::class, $book);

//////////

$data = [
    'id' => 1,
    'name' => 'PHP guide',
    'pubdate' => '2013-06-13',
];

$book = Book::initialize($data);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$dibiRow = new \Dibi\Row($data);
$row = new Row(Result::createInstance($dibiRow, 'book', $connection, $mapper), 1);
$book = Book::initialize($row);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$dibiRow = new \Dibi\Row($data);
$row = Result::createInstance($dibiRow, 'book', $connection, $mapper)->getRow(1);
$book = Book::initialize($row);

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

$book = Book::initialize(new ArrayObject($data));

Assert::type(Book::class, $book);
Assert::equal($data, $book->getData());

//////////

Assert::exception(
    function () {
        Book::initialize(false);
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'Argument $arg in Book::__construct must contain either null, array, instance of LeanMapper\Row or instance of Traversable, boolean given.'
);

Assert::exception(
    function () {
        Book::initialize('hello');
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'Argument $arg in Book::__construct must contain either null, array, instance of LeanMapper\Row or instance of Traversable, string given.'
);

//////////

$dibiRow = new \Dibi\Row($data);
$row = new Row(Result::createInstance($dibiRow, 'book', $connection, $mapper), 1);
$row->detach();

Assert::exception(
    function () use ($row) {
        Book::initialize($row);
    },
    LeanMapper\Exception\InvalidArgumentException::class,
    'It is not allowed to create entity Book from detached instance of LeanMapper\Row.'
);

//////////

$data = [
    'id' => 1,
    'name' => 'PHP guide',
    'pubdate' => '2013-06-13',
];

$book = new Book(
    $data['name'],
    $data['pubdate']
);

Assert::type(Book::class, $book);
Assert::equal([
    'name' => $data['name'],
    'pubdate' => $data['pubdate'],
], $book->getRowData());
