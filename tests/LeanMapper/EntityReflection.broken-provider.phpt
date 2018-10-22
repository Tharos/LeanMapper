<?php

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class BaseEntity extends LeanMapper\Entity
{
    protected static $reflectionProvider;


    public static function setReflectionProvider(LeanMapper\IEntityReflectionProvider $reflectionProvider)
    {
        self::$reflectionProvider = $reflectionProvider;
    }


    public static function getReflectionProvider()
    {
        return self::$reflectionProvider;
    }
}


/**
 * May create infinity loop.
 */
class BrokenReflectionProvider extends LeanMapper\DefaultEntityReflectionProvider
{
    public function getProperties(\ReflectionClass $entityClass, LeanMapper\IMapper $mapper = null)
    {
        return $entityClass->getEntityProperties();
    }


    public function getGetters(\ReflectionClass $entityClass)
    {
        return $entityClass->getGetters();
    }


    public function getSetters(\ReflectionClass $entityClass)
    {
        return (array) $entityClass->getSetter('foo');
    }
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


$reflectionProvider = new BrokenReflectionProvider;
BaseEntity::setReflectionProvider($reflectionProvider);

$reflection = Foo::getReflection($mapper);
Assert::same([], $reflection->getEntityProperties());
Assert::same([], $reflection->getGetters());
Assert::null($reflection->getSetter('setFoo'));
