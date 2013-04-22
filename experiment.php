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

$connection = new Connection('mysql:host=127.0.0.1;dbname=test', 'root', 'drubez');
$connection->onQuery[] = array($panel, 'logQuery');

$applications = $connection->table('application');

foreach ($applications as $application) {
	dump($application->title);
	dump($application->author->name . '(' . $application->author->related('application')->count() . ', ' . $application->author->related('application', 'maintainer_id')->count() . ')');

	$maintainer = $application->ref('author', 'maintainer_id');
	if ($maintainer !== null) {
		dump($maintainer->name);
	}
	foreach ($application->related('application_tag') as $tagRelation) {
		$tag = $tagRelation->tag;
		dump('Tag: ' . $tag->name . '(' . $tag->related('application_tag')->count() . ')');
	}
	echo '---------';
}