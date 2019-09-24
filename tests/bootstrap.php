<?php

use LeanMapper\Connection;
use LeanMapper\DefaultEntityFactory;
use LeanMapper\DefaultMapper;

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
class_alias('Tester\Assert', 'Assert');
date_default_timezone_set('Europe/Prague');

$_SERVER = array_intersect_key($_SERVER, array_flip(['PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv']));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = [];

if (extension_loaded('xdebug')) {
    xdebug_disable();
    Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
}

define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(__DIR__ . '/tmp/', 0777);
Tester\Helpers::purge(TEMP_DIR);

if (!copy(__DIR__ . '/db/library-ref.sq3', TEMP_DIR . '/library.sq3')) {
    echo 'Failed to copy SQLite database';
    exit(1);
}

$connection = new Connection([
    'driver' => 'sqlite3',
    'database' => TEMP_DIR . '/library.sq3',
]);

$mapper = new DefaultMapper(null);

$entityFactory = new DefaultEntityFactory;
