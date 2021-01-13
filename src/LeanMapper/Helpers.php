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

use Dibi\Row as DibiRow;

class Helpers
{

    public function __construct()
    {
        throw new Exception\UtilityClassException('Cannot instantiate utility class ' . get_called_class() . '.');
    }


    /**
     * @param  mixed $value
     */
    public static function isType($value, string $expectedType): bool
    {
        if ($value === null) {
            throw new Exception\InvalidValueException('Value null is not supported.');
        }

        if ($expectedType === 'bool' || $expectedType === 'boolean') {
            return is_bool($value);

        } elseif ($expectedType === 'int' || $expectedType === 'integer') {
            return is_int($value);

        } elseif ($expectedType === 'float') {
            return is_float($value);

        } elseif ($expectedType === 'string') {
            return is_string($value);

        } elseif ($expectedType === 'array') {
            return is_array($value);
        }

        return is_object($value) && ($value instanceof $expectedType);
    }


    /**
     * @param  mixed $value
     */
    public static function getType($value): string
    {
        return is_object($value) ? ('instance of ' . get_class($value)) : gettype($value);
    }


    /**
     * Trims namespace part from fully qualified class name
     */
    public static function trimNamespace(string $class): string
    {
        $class = explode('\\', $class);
        return end($class);
    }


    /**
     * @return array<string>
     */
    public static function split(string $pattern, string $subject): array
    {
        $res = preg_split($pattern, $subject);

        if (!is_array($res)) {
            throw new Exception\InvalidStateException('Function preg_split() failed.');
        }

        return $res;
    }


    /**
     * @param  array<string, mixed>|DibiRow<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function convertDbRow(string $table, $row, IMapper $mapper): array
    {
        if ($row instanceof DibiRow) {
            $row = $row->toArray();

        } elseif (!is_array($row)) {
            throw new Exception\InvalidArgumentException('DB row must be ' . DibiRow::class . '|array, ' . self::getType($row) . ' given.');
        }

        return $mapper->convertToRowData($table, $row);
    }


    /**
     * @param  array<array<string, mixed>|DibiRow<string, mixed>> $rows
     * @return array<array<string, mixed>>
     */
    public static function convertDbRows(string $table, array $rows, IMapper $mapper): array
    {
        $result = [];

        foreach ($rows as $k => $row) {
            $result[$k] = self::convertDbRow($table, $row, $mapper);
        }

        return $result;
    }


    /**
     * @param  array<string, mixed> $rowData
     * @return array<string, mixed>
     */
    public static function convertRowData(string $table, array $rowData, IMapper $mapper): array
    {
        return $mapper->convertFromRowData($table, $rowData);
    }

}
