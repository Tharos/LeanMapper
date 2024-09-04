<?php

declare(strict_types=1);

use LeanMapper\Entity;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\PropertyFactory;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class EmptyEntity extends Entity
{
}


test('<type>', function () {
    $entityReflection = new EntityReflection(new EmptyEntity());

    $property = PropertyFactory::createFromAnnotation('property', 'string $prop', $entityReflection);
    Assert::false($property->isNullable());
});


test('<type>|NULL', function () {
    $entityReflection = new EntityReflection(new EmptyEntity());

    $property = PropertyFactory::createFromAnnotation('property', 'string|NULL $prop', $entityReflection);
    Assert::true($property->isNullable());

    $property = PropertyFactory::createFromAnnotation('property', 'string|null $prop', $entityReflection);
    Assert::true($property->isNullable());
});


test('NULL|<type>', function () {
    $entityReflection = new EntityReflection(new EmptyEntity());

    $property = PropertyFactory::createFromAnnotation('property', 'NULL|string $prop', $entityReflection);
    Assert::true($property->isNullable());

    $property = PropertyFactory::createFromAnnotation('property', 'null|string $prop', $entityReflection);
    Assert::true($property->isNullable());
});


test('?<type>', function () {
    $entityReflection = new EntityReflection(new EmptyEntity());

    $property = PropertyFactory::createFromAnnotation('property', '?string $prop', $entityReflection);
    Assert::true($property->isNullable());
});
