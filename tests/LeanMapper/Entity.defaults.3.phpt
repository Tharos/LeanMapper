<?php

declare(strict_types=1);

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

///// conflicts

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'int $number m:default(10) m:default(20)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Multiple default value settings found in property definition: @property int \$number m:default(10) m:default(20) in entity EmptyEntity."
);

Assert::exception(
    function () use ($entityReflection) {
        PropertyFactory::createFromAnnotation('property', 'int $number = 20 m:default(10)', $entityReflection);
    },
    'LeanMapper\Exception\InvalidAnnotationException',
    "Multiple default value settings found in property definition: @property int \$number = 20 m:default(10) in entity EmptyEntity."
);
