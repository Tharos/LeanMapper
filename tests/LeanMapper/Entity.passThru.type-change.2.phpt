<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../libs/DummyDrivers.php';

//////////

/**
 * @property int $id
 * @property array|NULL $attrs m:passThru(jsonDecodeData|jsonEncodeData)
 */
class Foo extends LeanMapper\Entity
{
    protected function jsonEncodeData($data)
    {
        return !empty($data) ? json_encode($data) : null;
    }


    protected function jsonDecodeData($data)
    {
        return !empty($data) ? json_decode($data, true) : [];
    }
}


class FooRepository extends LeanMapper\Repository
{
}

//////////

$foo = new Foo;
$foo->attrs = ['foo' => 'bar'];
Assert::same([
    'attrs' => '{"foo":"bar"}',
], $foo->getRowData());
Assert::same(['foo' => 'bar'], $foo->attrs);

// persist
$driver = new PostgreDummyDriver;
$driver->setResultData('INSERT INTO "foo" ("attrs") VALUES (\'{"foo":"bar"}\')', []);
$driver->setResultData('SELECT LASTVAL()', [[1]]);
$connection = new LeanMapper\Connection(['driver' => $driver]);
$repository = new FooRepository($connection, $mapper, $entityFactory);
$repository->persist($foo);
