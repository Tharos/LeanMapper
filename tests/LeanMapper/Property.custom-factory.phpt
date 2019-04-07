<?php

use LeanMapper\Reflection\AnnotationsParser;
use LeanMapper\Reflection\EntityReflection;
use LeanMapper\Reflection\Property;
use LeanMapper\Reflection\PropertyFactory;
use LeanMapper\Reflection\PropertyFilters;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

//////////

class CustomReflectionProvider extends LeanMapper\DefaultEntityReflectionProvider
{
    /** @var PropertyFilters|null */
    public static $customFilters;


    public function getProperties(EntityReflection $entityClass, LeanMapper\IMapper $mapper = null)
    {
        $properties = [];
        $annotationTypes = ['property', 'property-read'];
        foreach ($this->getFamilyLine($entityClass) as $member) {
            foreach ($annotationTypes as $annotationType) {
                foreach (AnnotationsParser::parseMultiLineAnnotationValues($annotationType, $member->getDocComment()) as $definition) {
                    $properties[] = PropertyFactory::createFromAnnotation($annotationType, $definition, $member, $mapper, [$this, 'createCustomProperty']);
                }
            }
        }
        return $properties;
    }


    public function createCustomProperty(
        $name,
        $entityReflection,
        $column,
        $propertyType,
        $isWritable,
        $isNullable,
        $containsCollection,
        $hasDefaultValue,
        $defaultValue,
        $relationship,
        $propertyMethods,
        $propertyFilters,
        $propertyPasses,
        $propertyValuesEnum,
        $customFlags
    )
    {
        if ($propertyFilters !== null) {
            $propertyFilters = self::$customFilters;
        }
        return new Property(
            $name,
            $entityReflection,
            $column,
            $propertyType,
            $isWritable,
            $isNullable,
            $containsCollection,
            $hasDefaultValue,
            $defaultValue,
            $relationship,
            $propertyMethods,
            $propertyFilters,
            $propertyPasses,
            $propertyValuesEnum,
            $customFlags
        );
    }
}

/**
 * @property int $id
 * @property Book $parent m:belongsToOne m:filter(somefilter)
 */
class Book extends LeanMapper\Entity
{
    public static function getReflectionProvider()
    {
        return new CustomReflectionProvider;
    }
}

//////////

$propertyFilters = new PropertyFilters(['myfilter'], []);
CustomReflectionProvider::$customFilters = $propertyFilters;

$reflection = Book::getReflection($mapper);
$property = $reflection->getEntityProperty('parent');
Assert::same($propertyFilters->getFilters(), $property->getFilters());
