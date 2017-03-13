<?php

use LeanMapper\Entity;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

/**
 * @property string $web (website)
 * @property string $site (website)
 */
class AuthorWithDuplicatedColumn extends Entity
{
}

Assert::exception(
    function () {
        $reflection = AuthorWithDuplicatedColumn::getReflection();
    },
    'LeanMapper\Exception\InvalidStateException',
    "Mapping collision in property 'site' (column 'website') in entity AuthorWithDuplicatedColumn. Please fix mapping or make chosen properties read only (using property-read)."
);

//////////

/**
 * @property int $id
 * @property string $id
 */
class AuthorWithDuplicatedProperty1 extends Entity
{
}

Assert::exception(
    function () {
        $reflection = AuthorWithDuplicatedProperty1::getReflection();
    },
    'LeanMapper\Exception\InvalidStateException',
    "Duplicated property 'id' in entity AuthorWithDuplicatedProperty1. Please fix property name."
);

//////////

/**
 * @property int $id
 * @property string $id (name)
 */
class AuthorWithDuplicatedProperty2 extends Entity
{
}

Assert::exception(
    function () {
        $reflection = AuthorWithDuplicatedProperty2::getReflection();
    },
    'LeanMapper\Exception\InvalidStateException',
    "Duplicated property 'id' in entity AuthorWithDuplicatedProperty2. Please fix property name."
);

//////////

/**
 * @property int $id
 * @property-read string $id
 */
class AuthorWithDuplicatedReadProperty extends Entity
{
}

Assert::exception(
    function () {
        $reflection = AuthorWithDuplicatedReadProperty::getReflection();
    },
    'LeanMapper\Exception\InvalidStateException',
    "Duplicated property 'id' in entity AuthorWithDuplicatedReadProperty. Please fix property name."
);
