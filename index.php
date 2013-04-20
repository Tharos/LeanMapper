<?php

/**
 * @author Vojtěch Kohout
 */

use Nette\Diagnostics\Debugger;
use Model\Repository\ApplicationRepository;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';

$panel = new DibiNettePanel();
Debugger::addPanel($panel);

Debugger::enable();
Debugger::$strictMode = true;

$connection = new DibiConnection(array(
	'driver' => 'mysql',
	'host' => '127.0.0.1',
	'username' => 'root',
	'password' => 'drubez',
	'database' => 'test',
));
$connection->onEvent[] = array($panel, 'logEvent');

$repo = new ApplicationRepository($connection);

$applications = $repo->findAll();

foreach ($applications as $application) {
	dump($application->getTitle());
	dump($application->getAuthor()->getName());
}
