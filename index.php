<?php

/**
 * @author Vojtěch Kohout
 */

use Model\Repository\AuthorRepository;
use Nette\Diagnostics\Debugger;
use Model\Repository\ApplicationRepository;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';

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

echo '<h2>„Backjoin“</h2>';

$repo = new AuthorRepository($connection);

$authors = $repo->findAll();

foreach ($authors as $author) {
	dump($author->name);
	foreach ($author->referencingTags as $tag) {
		dump('Tag: ' . $tag->name);
	}

	echo '---------';
}

echo '<h2>Basic example</h2>';

$repo = new ApplicationRepository($connection);

$applications = $repo->findAll();

foreach ($applications as $application) {
	dump($application->title);
	dump($application->author->name . '(' . $application->author->authorshipCount . ', ' . $application->author->maintainershipCount . ')');

	$maintainer = $application->maintainer;
	if ($maintainer !== null) {
		dump($maintainer->name);
	}
	foreach ($application->tags as $tag) {
		if ($tag !== null) {
			dump('Tag: ' . $tag->name . '(' . $tag->usageCount . ')');
		}
	}
	echo '---------';
}