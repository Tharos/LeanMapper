<?php

/**
 * @author Vojtěch Kohout
 */

use Nette\Diagnostics\Debugger;
use Model\Repository\ApplicationRepository;

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

$repo = new ApplicationRepository($connection);

$application = $repo->find(4);

dump($application->tags);