<?php

use LeanMapper\Entity;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\PropertyFactory;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

////////////////////

class EmptyEntity extends Entity
{
}

$entityReflection = new EntityReflection(new EmptyEntity());

////////////////////

///// booleans

$property = PropertyFactory::createFromAnnotation('property', 'bool $active m:default(true)', $entityReflection);

Assert::equal(true, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'bool $active m:default(TrUe)', $entityReflection);

Assert::equal(true, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'bool $active m:default(FALSE)', $entityReflection);

Assert::equal(false, $property->getDefaultValue());

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'bool $active m:default(foobar)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Invalid property definition given: @property bool \$active m:default(foobar) in entity EmptyEntity, property of type boolean cannot have default value 'foobar'."
);

///// integers

$property = PropertyFactory::createFromAnnotation('property', 'int $count m:default(10)', $entityReflection);

Assert::equal(10, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'int $count m:default(0x1F)', $entityReflection);

Assert::equal(31, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'int $count m:default(0123)', $entityReflection);

Assert::equal(83, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'int $count m:default("12")', $entityReflection);

Assert::equal(12, $property->getDefaultValue());

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'int $count m:default(true)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Invalid property definition given: @property int \$count m:default(true) in entity EmptyEntity, property of type integer cannot have default value 'true'."
);

$property = PropertyFactory::createFromAnnotation('property', 'int $count m:default(-12)', $entityReflection);

Assert::equal(-12, $property->getDefaultValue());

///// floats

$property = PropertyFactory::createFromAnnotation('property', 'float $count m:default(10.5)', $entityReflection);

Assert::equal(10.5, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'float $count m:default(-2.2e-3)', $entityReflection);

Assert::equal(-0.0022, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'float $count m:default("-2.4e-3")', $entityReflection);

Assert::equal(-0.0024, $property->getDefaultValue());

///// strings

$property = PropertyFactory::createFromAnnotation('property', 'string $title m:default(test)', $entityReflection);

Assert::equal('test', $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'string $title m:default(foo bar)', $entityReflection);

Assert::equal('foo', $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'string $title m:default("foo bar")', $entityReflection);

Assert::equal('foo bar', $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', "string \$title m:default(McDonald's)", $entityReflection);

Assert::equal("McDonald's", $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', "string \$title m:default('McDonald\'s restaurant')", $entityReflection);

Assert::equal("McDonald's restaurant", $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', "string \$title m:default('McDonald\\'s restaurant')", $entityReflection);

Assert::equal("McDonald's restaurant", $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'string $title m:default("foo\"b\'ar")', $entityReflection);

Assert::equal('foo"b\'ar', $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'string $title m:default("")', $entityReflection);

Assert::equal('', $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', "string \$title m:default('')", $entityReflection);

Assert::equal('', $property->getDefaultValue());

///// arrays

$property = PropertyFactory::createFromAnnotation('property', 'array $list m:default(array())', $entityReflection);

Assert::equal([], $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'array $list m:default(ARRAY())', $entityReflection);

Assert::equal([], $property->getDefaultValue());

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'array $list m:default(ARRAY)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Invalid property definition given: @property array \$list m:default(ARRAY) in entity EmptyEntity, property of type array cannot have default value 'ARRAY'."
);

///// null

$property = PropertyFactory::createFromAnnotation('property', 'string|null $title m:default(null)', $entityReflection);

Assert::equal(null, $property->getDefaultValue());

$property = PropertyFactory::createFromAnnotation('property', 'string|null $title m:default(NULL)', $entityReflection);

Assert::equal(null, $property->getDefaultValue());

///// objects

$property = PropertyFactory::createFromAnnotation('property', 'NULL|DateTime $created m:default(null)', $entityReflection);

Assert::equal(null, $property->getDefaultValue());

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'DateTime $created m:default(10)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Invalid property definition given: @property DateTime \$created m:default(10) in entity EmptyEntity, only properties of basic types may have default values specified."
);
