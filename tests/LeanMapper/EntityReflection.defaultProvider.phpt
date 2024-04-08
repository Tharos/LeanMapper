<?php

declare(strict_types=1);

use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @property int $id
 * @property string $foo
 */
class Foo extends \LeanMapper\Entity
{
    public function getFoo(): string
    {
        return $this->foo;
    }


    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }
}


$reflection = Foo::getReflection(Tests::createMapper());

////////

$property = $reflection->getEntityProperty('foo');
Assert::notNull($property);

////////

Assert::null($reflection->getGetter('getId'));

$getter = $reflection->getGetter('getFoo');
Assert::notNull($getter);

////////

Assert::null($reflection->getSetter('setId'));

$setter = $reflection->getSetter('setFoo');
Assert::notNull($setter);
