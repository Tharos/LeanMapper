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

namespace LeanMapper\Reflection;

use LeanMapper\Exception\UtilityClassException;

/**
 * Simple class annotations parser
 *
 * @author Vojtěch Kohout
 */
class AnnotationsParser
{

    /**
     * @throws UtilityClassException
     */
    public function __construct()
    {
        throw new UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
    }


    /**
     * Parse value of requested simple annotation from given doc comment
     */
    public static function parseSimpleAnnotationValue(string $annotation, string $docComment): ?string
    {
        $matches = [];
        preg_match("#@$annotation\\s+([^\\s]+)#", $docComment, $matches);
        return !empty($matches) ? $matches[1] : null;
    }


    /**
     * Parse value pieces of requested annotation from given doc comment
     *
     * @return array<string>
     */
    public static function parseAnnotationValues(string $annotation, string $docComment): array
    {
        $matches = [];
        preg_match_all("#@$annotation\\s+([^@\\n\\r]*)#", $docComment, $matches);
        return $matches[1];
    }


    /**
     * Parse value pieces of requested multiline annotation from given doc comment
     *
     * @return array<string>
     */
    public static function parseMultiLineAnnotationValues(string $annotation, string $docComment): array
    {
        $matches = [];
        preg_match_all("#@$annotation\\h+([^\n\r@]+(?:\\s*\*\\h{2,}+[^\n\r@]+)*)#", $docComment, $matches);
        return $matches[1];
    }

}
