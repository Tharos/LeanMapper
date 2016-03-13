<?php

/**
 * Test: LeanMapperQuery\Query limit and offset.
 * @author Michal Bohuslávek
 */

use LeanMapper\Fluent;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @param $table
 * @return \LeanMapper\Fluent
 */
function getFluent($table)
{
    global $connection;
    $fluent = new Fluent($connection);
    return $fluent->select('%n.*', $table)->from($table);
}

$fluent = getFluent('book');
Assert::same('SELECT [book].* FROM [book]', (string)$fluent);

$fluent = getFluent('book')->limit(10);
Assert::same('  SELECT [book].* FROM [book] LIMIT 10', (string)$fluent);

$fluent = getFluent('book')->limit(10)->offset(50);
Assert::same('  SELECT [book].* FROM [book] LIMIT 10 OFFSET 50', (string)$fluent);
