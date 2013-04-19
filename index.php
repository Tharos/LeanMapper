<?php

/**
 * @author Vojtěch Kohout
 */

use Nette\Diagnostics\Debugger;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';

$panel = new DibiNettePanel();
Debugger::addPanel($panel);

Debugger::enable();

$connection = new DibiConnection(array(
	'driver' => 'mysql',
	'host' => '127.0.0.1',
	'username' => 'root',
	'password' => 'drubez',
	'database' => 'test',
));
$connection->onEvent[] = array($panel, 'logEvent');

