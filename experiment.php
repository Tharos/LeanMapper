<?php

/**
 * @author Vojtěch Kohout
 */

use Nette\Database\Connection;
use Nette\Database\Diagnostics\ConnectionPanel;
use Nette\Diagnostics\Debugger;
use Model\Repository\ApplicationRepository;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';

$panel = new ConnectionPanel;
Debugger::addPanel($panel);

Debugger::enable();
Debugger::$strictMode = true;

$connection = new Connection('mysql:host=localhost;dbname=test', 'root', 'drubez');
$connection->onQuery[] = array($panel, 'logQuery');

$applications = $connection->table('application');

dump($applications[1]->author->name);