<?php

use Nette\Configurator;
use Nette\Utils\Random;

require_once __DIR__ . '/../bootstrap.php';

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(['container' => ['class' => 'SystemContainer_' . Random::generate()]]);
$configurator->addConfig(__DIR__ . '/DI.extension.1.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();
\Tester\Assert::true($container instanceof \Nette\DI\Container);

$mapper = $container->getByType('LeanMapper\DefaultMapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);

/********************************************************/
// service overriding
/********************************************************/

class MyMapper extends \LeanMapper\DefaultMapper
{

}

$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->addParameters(['container' => ['class' => 'SystemContainer_' . Random::generate()]]);
$configurator->addConfig(__DIR__ . '/DI.extension.2.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();

$mapper = $container->getByType('LeanMapper\DefaultMapper');
\Tester\Assert::true($mapper instanceof MyMapper);
$mapper = $container->getByType('MyMapper');
\Tester\Assert::true($mapper instanceof MyMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof LeanMapper\DefaultMapper);
$mapper = $container->getService('LeanMapper.mapper');
\Tester\Assert::true($mapper instanceof MyMapper);


/// debug mode + FileLogger
$configurator = new Configurator;
$configurator->setTempDirectory(TEMP_DIR);
$configurator->setDebugMode(true);
$configurator->addParameters([
    'container' => ['class' => 'SystemContainer_' . Random::generate()],
]);
$configurator->addConfig(__DIR__ . '/DI.extension.3.neon');

/** @var \Nette\DI\Container $container */
$container = $configurator->createContainer();
\Tester\Assert::true($container instanceof \Nette\DI\Container);

$connection = $container->getService('LeanMapper.connection');
\Tester\Assert::same([
    [$container->getService('LeanMapper.panel'), 'logEvent'],
    [$container->getService('LeanMapper.fileLogger'), 'logEvent'],
], $connection->onEvent);
