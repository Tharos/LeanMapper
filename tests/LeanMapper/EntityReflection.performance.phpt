<?php

declare(strict_types=1);

use LeanMapper\Reflection\EntityReflection;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class BaseEntity extends LeanMapper\Entity
{
    protected static $reflectionProvider;


    public static function setReflectionProvider(LeanMapper\IEntityReflectionProvider $reflectionProvider): void
    {
        self::$reflectionProvider = $reflectionProvider;
    }


    public static function getReflectionProvider(): LeanMapper\IEntityReflectionProvider
    {
        return self::$reflectionProvider;
    }
}


class CustomReflectionProvider extends LeanMapper\DefaultEntityReflectionProvider
{
    public $propertyParsing = 0;


    public function getProperties(EntityReflection $entityClass, ?LeanMapper\IMapper $mapper = null): array
    {
        $this->propertyParsing++;
        return parent::getProperties($entityClass, $mapper);
    }
}


/**
 * @property int $id
 * @property string $foo
 */
class Foo extends BaseEntity
{

}


/**
 * @property string $bar
 */
class Bar extends Foo
{

}


/**
 * @property int $unacceptable
 */
class Hell extends Bar
{

}


/**
 * @property int $unbearable
 */
class Exponential extends Hell
{

}


$reflectionProvider = new CustomReflectionProvider;
$reflectionProvider->propertyParsing = 0;
BaseEntity::setReflectionProvider($reflectionProvider);

$foo = new Foo();
$bar = new Bar();
$hell = new Hell();
$exp = new Exponential();

new Foo();
new Bar();
new Hell();
new Exponential();

Assert::same(4, $reflectionProvider->propertyParsing);
