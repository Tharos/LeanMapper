<?php

declare(strict_types=1);

use LeanMapper\Exception\InvalidValueException;
use LeanMapper\Helpers;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

test('bool', function () {
    // bool => bool
    Assert::true(Helpers::isType(true, 'bool'));
    Assert::same(true, Helpers::convertType(true, 'bool'));

    Assert::true(Helpers::isType(false, 'bool'));
    Assert::same(false, Helpers::convertType(false, 'bool'));

    // int => bool
    Assert::false(Helpers::isType(0, 'bool'));
    Assert::same(true, Helpers::convertType(1, 'bool'));
    Assert::same(false, Helpers::convertType(0, 'bool'));

    // float => bool
    Assert::false(Helpers::isType(0.0, 'bool'));
    Assert::same(true, Helpers::convertType(1.0, 'bool'));
    Assert::same(false, Helpers::convertType(0.0, 'bool'));

    // string => bool
    Assert::false(Helpers::isType('1', 'bool'));
    Assert::same(true, Helpers::convertType('1', 'bool'));
    Assert::same(false, Helpers::convertType('0', 'bool'));
    Assert::same(false, Helpers::convertType('', 'bool'));

    // array => bool
    Assert::false(Helpers::isType([], 'bool'));
    Assert::same(true, Helpers::convertType([2], 'bool'));
    Assert::same(false, Helpers::convertType([], 'bool'));

    // object => bool
    $datetime = new \DateTimeImmutable('2024-06-14 00:00:00');
    Assert::false(Helpers::isType($datetime, 'bool'));
    Assert::same(true, Helpers::convertType($datetime, 'bool'));
});


test('int', function () {
    // bool => int
    Assert::false(Helpers::isType(true, 'int'));
    Assert::same(1, Helpers::convertType(true, 'int'));
    Assert::same(0, Helpers::convertType(false, 'int'));

    // int => int
    Assert::true(Helpers::isType(1, 'int'));
    Assert::same(1, Helpers::convertType(1, 'int'));

    // float => int
    Assert::false(Helpers::isType(0.0, 'int'));
    Assert::same(1, Helpers::convertType(1.1, 'int'));
    Assert::same(1, Helpers::convertType(1.2, 'int'));

    // string => int
    Assert::false(Helpers::isType('1', 'int'));
    Assert::same(1, Helpers::convertType('1', 'int'));
    Assert::same(0, Helpers::convertType('0', 'int'));
    Assert::same(0, Helpers::convertType('', 'int'));

    // array => int
    Assert::false(Helpers::isType([], 'int'));
    Assert::exception(function () {
        Helpers::convertType([], 'int');
    }, InvalidValueException::class, 'Given value cannot be converted to int.');

    // object => int
    Assert::false(Helpers::isType(new \DateTimeImmutable, 'int'));
    Assert::exception(function () {
        Helpers::convertType(new \DateTimeImmutable, 'int');
    }, InvalidValueException::class, 'Given value cannot be converted to int.');
});


test('float', function () {
    // bool => float
    Assert::false(Helpers::isType(true, 'float'));
    Assert::same(1.0, Helpers::convertType(true, 'float'));
    Assert::same(0.0, Helpers::convertType(false, 'float'));

    // int => float
    Assert::false(Helpers::isType(0, 'float'));
    Assert::same(1.0, Helpers::convertType(1, 'float'));
    Assert::same(2.0, Helpers::convertType(2, 'float'));

    // float => float
    Assert::true(Helpers::isType(0.0, 'float'));
    Assert::same(1.0, Helpers::convertType(1.0, 'float'));

    // string => float
    Assert::false(Helpers::isType('1', 'float'));
    Assert::same(1.0, Helpers::convertType('1', 'float'));
    Assert::same(0.0, Helpers::convertType('', 'float'));

    // array => float
    Assert::false(Helpers::isType([], 'float'));
    Assert::exception(function () {
        Helpers::convertType([], 'float');
    }, InvalidValueException::class, 'Given value cannot be converted to float.');

    // object => float
    Assert::false(Helpers::isType(new \DateTimeImmutable, 'float'));
    Assert::exception(function () {
        Helpers::convertType(new \DateTimeImmutable, 'float');
    }, InvalidValueException::class, 'Given value cannot be converted to float.');
});


test('string', function () {
    // bool => string
    Assert::false(Helpers::isType(true, 'string'));
    Assert::same('1', Helpers::convertType(true, 'string'));
    Assert::same('', Helpers::convertType(false, 'string'));

    // int => string
    Assert::false(Helpers::isType(0, 'string'));
    Assert::same('1', Helpers::convertType(1, 'string'));
    Assert::same('0', Helpers::convertType(0, 'string'));

    // float => string
    Assert::false(Helpers::isType(1.0, 'string'));
    Assert::same('1', Helpers::convertType(1.0, 'string'));
    Assert::same('2.1', Helpers::convertType(2.1, 'string'));

    // string => string
    Assert::true(Helpers::isType('0', 'string'));
    Assert::true(Helpers::isType('Hello', 'string'));
    Assert::same('Hello', Helpers::convertType('Hello', 'string'));

    // array => string
    Assert::false(Helpers::isType([], 'string'));
    Assert::exception(function () {
        Helpers::convertType([], 'string');
    }, InvalidValueException::class, 'Given value cannot be converted to string.');

    // object => string
    Assert::false(Helpers::isType(new \DateTimeImmutable, 'string'));
    Assert::exception(function () {
        Helpers::convertType(new \DateTimeImmutable, 'string');
    }, InvalidValueException::class, 'Given value cannot be converted to string.');
});


test('array', function () {
    // bool => array
    Assert::false(Helpers::isType(true, 'array'));
    Assert::same([true], Helpers::convertType(true, 'array'));
    Assert::same([false], Helpers::convertType(false, 'array'));

    // int => array
    Assert::false(Helpers::isType(0, 'array'));
    Assert::same([1], Helpers::convertType(1, 'array'));
    Assert::same([0], Helpers::convertType(0, 'array'));

    // float => array
    Assert::false(Helpers::isType(1.0, 'array'));
    Assert::same([1.0], Helpers::convertType(1.0, 'array'));
    Assert::same([2.1], Helpers::convertType(2.1, 'array'));

    // string => array
    Assert::false(Helpers::isType('Hello', 'array'));
    Assert::same(['Hello'], Helpers::convertType('Hello', 'array'));

    // array => array
    Assert::true(Helpers::isType([], 'array'));
    Assert::same([1], Helpers::convertType([1], 'array'));

    // object => array
    $obj = new \stdClass;
    $obj->name = 'test';

    Assert::false(Helpers::isType($obj, 'array'));
    Assert::same(['name' => 'test'], Helpers::convertType($obj, 'array'));
});


test('object', function () {
    // bool => object
    Assert::false(Helpers::isType(true, \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType(true, \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');

    // int => object
    Assert::false(Helpers::isType(0, \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType(0, \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');

    // float => object
    Assert::false(Helpers::isType(1.0, \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType(1.0, \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');

    // string => object
    Assert::false(Helpers::isType('Hello', \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType('Hello', \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');

    // array => object
    Assert::false(Helpers::isType([], \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType([], \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');

    // object => object
    $obj = new \stdClass;
    $obj->name = 'test';

    Assert::true(Helpers::isType($obj, \stdClass::class));
    Assert::same($obj, Helpers::convertType($obj, \stdClass::class));
    Assert::exception(function () {
        Helpers::convertType(new \DateTimeImmutable, \stdClass::class);
    }, InvalidValueException::class, 'Given value cannot be converted to stdClass.');
});
