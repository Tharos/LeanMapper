<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
 */

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
     * @return string
     */
    public static function getType($value)
    {
        return is_object($value) ? ('instance of ' . get_class($value)) : gettype($value);
    }


    /**
     * @param  string $table
     * @param  array|DibiRow $row
     * @return array
     */
    public static function convertDbRow($table, $row, IMapper $mapper)
    {
        if ($row instanceof DibiRow) {
            $row = $row->toArray();

        } elseif (!is_array($row)) {
            throw new Exception\InvalidArgumentException('DB row must be ' . DibiRow::class . '|array, ' . self::getType($row) . ' given.');
        }

        if ($mapper instanceof IRowMapper) {
            return $mapper->convertToRowData($table, $row);
        }

        return $row;
    }


    /**
     * @param  array<array>|DibiRow[] $rows
     * @return array<array>
     */
    public static function convertDbRows($table, array $rows, IMapper $mapper)
    {
        $result = [];

        foreach ($rows as $k => $row) {
            $result[$k] = self::convertDbRow($table, $row, $mapper);
        }

        return $result;
    }


    public static function convertRowData($table, array $rowData, IMapper $mapper)
    {
        if ($mapper instanceof IRowMapper) {
            return $mapper->convertFromRowData($table, $rowData);
        }

        return $rowData;
    }

}
