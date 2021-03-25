<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @property string $bar
 */
abstract class BaseEntity extends LeanMapper\Entity
{
}


/**
 * @property int $id
 * @property string $foo
 */
class Foo extends BaseEntity
{
    public function getFoo()
    {
        return $this->row->foo;
    }


    public function setFoo($foo)
    {
        $this->row->foo = $foo;
    }
}


class Mapper extends LeanMapper\DefaultMapper
{
    public function getColumn(string $entityClass, string $field): string
    {
        return 'test_' . $field;
    }
}

$mapper = new Mapper;

$reflection = Foo::getReflection($mapper);
Assert::same('test_id', $reflection->getEntityProperty('id')->getColumn());
Assert::same('test_foo', $reflection->getEntityProperty('foo')->getColumn());
Assert::same('test_bar', $reflection->getEntityProperty('bar')->getColumn()); // parent property

$parentReflection = $reflection->getParentClass();
Assert::same('test_bar', $parentReflection->getEntityProperty('bar')->getColumn()); // parent property
