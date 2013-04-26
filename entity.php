<?php

/**
 * @author Vojtěch Kohout
 */

use LeanMapper\Reflection\EntityReflection;
use Nette\Diagnostics\Debugger;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Model/Entity/Book.php';

$panel = new DibiNettePanel;
Debugger::addPanel($panel);

Debugger::enable();
Debugger::$strictMode = true;

$connection = new DibiConnection(array(
	'driver' => 'pdo',
	'dsn' => 'mysql:host=127.0.0.1;dbname=test',
	'username' => 'root',
	'password' => 'drubez',
));
$connection->onEvent[] = array($panel, 'logEvent');

$reflection = new EntityReflection('Model\Entity\Book');
dump($reflection->getEntityProperty('authors'));