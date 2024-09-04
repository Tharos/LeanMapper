<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

declare(strict_types=1);

namespace LeanMapper;

trait Initialize
{
    /**
     * Creates & initializes entity instance from given argument
     *
     * @param Row|iterable<string, mixed>|null $arg
     */
    public static function initialize($arg): Entity
    {
        $reflection = static::getReflection();
        $entity = $reflection->newInstanceWithoutConstructor();

        if ($arg instanceof Row) {
            if ($arg->isDetached()) {
                throw new Exception\InvalidArgumentException(
                    'It is not allowed to create entity ' . get_called_class() . ' from detached instance of ' . Row::class . '.'
                );
            }

            $entity->row = $arg;
            $entity->mapper = $arg->getMapper();

        } else {
            $entity->row = Result::createDetachedInstance()->getRow();

            foreach ($entity->getCurrentReflection()->getEntityProperties() as $property) {
                if ($property->hasDefaultValue()) {
                    $propertyName = $property->getName();
                    $entity->set($propertyName, $property->getDefaultValue());
                }
            }

            $entity->initDefaults();

            if ($arg !== null) {
                if (!is_iterable($arg)) {
                    $type = Helpers::getType($arg);
                    throw new Exception\InvalidArgumentException(
                        "Argument \$arg in " . get_called_class(
                        ) . "::__construct must contain either null, array, instance of " . Row::class . " or instance of " . \Traversable::class . ", $type given."
                    );
                }

                $entity->assign($arg);
            }
        }

        return $entity;
    }
}
