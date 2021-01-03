<?php

declare(strict_types=1);

namespace LeanMapper\Tests\PhpStan;

class DibiFluentDynamicReturnTypeExtension implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return \Dibi\Fluent::class;
    }


    public function isMethodSupported(\PHPStan\Reflection\MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'execute';
    }


    public function getTypeFromMethodCall(
        \PHPStan\Reflection\MethodReflection $methodReflection,
        \PhpParser\Node\Expr\MethodCall $methodCall,
        \PHPStan\Analyser\Scope $scope
    ): \PHPStan\Type\Type
    {
        if (count($methodCall->args) === 0) {
            return new \PHPStan\Type\ObjectType(\Dibi\Result::class);
        }

        return new \PHPStan\Type\IntegerType;
    }

}
