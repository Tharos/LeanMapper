<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class BaseEntity extends LeanMapper\Entity
{
    public static function getReflectionProvider()
    {
        return parent::getReflectionProvider();
    }
}


class CustomReflectionProvider extends LeanMapper\DefaultEntityReflectionProvider
{
}


/**
 * @property int $id
 * @property string $foo
 */
class FooDefault extends BaseEntity
{
}


/**
 * @property string $bar
 */
class BarDefault extends FooDefault
{
}


/**
 * @property int $id
 * @property string $foo
 */
class FooCustom extends BaseEntity
{
    private static $reflectionProvider = null;


    public static function getReflectionProvider()
    {
        if (self::$reflectionProvider === null) {
            self::$reflectionProvider = new CustomReflectionProvider;
        }
        return self::$reflectionProvider;
    }
}


/**
 * @property string $bar
 */
class BarCustom extends FooCustom
{
}


$fooDefaultProvider = FooDefault::getReflectionProvider();
$barDefaultProvider = BarDefault::getReflectionProvider();
$fooCustomProvider = FooCustom::getReflectionProvider();
$barCustomProvider = BarCustom::getReflectionProvider();

Assert::same('LeanMapper\DefaultEntityReflectionProvider', get_class($fooDefaultProvider));
Assert::same('LeanMapper\DefaultEntityReflectionProvider', get_class($barDefaultProvider));
Assert::same('CustomReflectionProvider', get_class($fooCustomProvider));
Assert::same('CustomReflectionProvider', get_class($barCustomProvider));

Assert::same($fooDefaultProvider, $barDefaultProvider);
Assert::same($fooCustomProvider, $barCustomProvider);
