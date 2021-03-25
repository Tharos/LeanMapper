<?php

declare(strict_types=1);

use LeanMapper\Entity;
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

    protected function initDefaults(): void
    {
        $this->assign(
            [
                'name' => 'Default name',
                'pubdate' => '2013-01-01 08:00:00',
            ]
        );
    }

}

/**
 * @property string $name
 */
class Role extends LeanMapper\Entity
{
    protected function initDefaults(): void
    {
        $this->assign(
            [
                'name' => 'Guest',
            ]
        );
    }
}

/**
 * @property null|string $firstname
 * @property string $surname
 */
class User extends LeanMapper\Entity
{
    protected function initDefaults(): void
    {
        $this->firstname = null;
    }
}

//////////

$book = new Book;

Assert::type(Book::class, $book);

Assert::equal(
    [
        'name' => 'Default name',
        'pubdate' => '2013-01-01 08:00:00',
    ],
    $book->getModifiedRowData()
);

Assert::equal(
    [
        'name' => 'Default name',
        'pubdate' => '2013-01-01 08:00:00',
    ],
    $book->getRowData()
);

Assert::equal('Default name', $book->name);

Assert::exception(
    function () use ($book) {
        $book->id;
    },
    LeanMapper\Exception\Exception::class,
    "Cannot get value of property 'id' in entity Book due to low-level failure: Missing 'id' column in row with id -1."
);

Assert::exception(
    function () use ($book) {
        $book->getData();
    },
    LeanMapper\Exception\Exception::class,
    "Cannot get value of property 'id' in entity Book due to low-level failure: Missing 'id' column in row with id -1."
);

$user = new User(
    [
        'firstname' => 'Vojtěch',
        'surname' => 'Kohout',
    ]
);

Assert::equal('Vojtěch', $user->firstname);
Assert::equal('Kohout', $user->surname);

$role = new Role(['name' => 'User']);
Assert::equal('User', $role->name);
$role = new Role();
Assert::equal('Guest', $role->name);
