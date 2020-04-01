<?php

declare(strict_types=1);

namespace LeanMapper\Bridges\Nette\DI;

use Nette;
use Nette\Loaders\RobotLoader;

class LeanMapperExtension extends Nette\DI\CompilerExtension
{

    public $defaults = [
        'db' => [],
        'profiler' => true,
        'scanDirs' => null,
        'logFile' => null,
    ];



    public function __construct($scanDirs = NULL)
    {
        $this->defaults['scanDirs'] = $scanDirs;
    }



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

        $index = 1;
        foreach ($this->findRepositories($config) as $repositoryClass) {
            $container->addDefinition($this->prefix('table.' . $index++))->setFactory($repositoryClass);
        }

        $container->addDefinition($this->prefix('mapper'))
            ->setFactory('LeanMapper\DefaultMapper');

        $container->addDefinition($this->prefix('entityFactory'))
            ->setFactory('LeanMapper\DefaultEntityFactory');

        $connection = $container->addDefinition($this->prefix('connection'))
            ->setFactory('LeanMapper\Connection', [$config['db']]);

        if (isset($config['db']['flags'])) {
            $flags = 0;
            foreach ((array)$config['db']['flags'] as $flag) {
                $flags |= constant($flag);
            }
            $config['db']['flags'] = $flags;
        }

        if (class_exists('Tracy\Debugger') && $container->parameters['debugMode'] && $config['profiler']) {
            $panel = $container->addDefinition($this->prefix('panel'))->setFactory('Dibi\Bridges\Tracy\Panel');
            $connection->addSetup([$panel, 'register'], [$connection]);
            if ($config['logFile']) {
                $fileLogger = $container->addDefinition($this->prefix('fileLogger'))
                    ->setFactory('Dibi\Loggers\FileLogger', [$config['logFile']]);
                $connection->addSetup('$service->onEvent[] = ?', [
                    [$fileLogger, 'logEvent'],
                ]);
            }
        }
    }



    private function findRepositories($config)
    {
        $classes = [];

        if ($config['scanDirs']) {
            $robot = new RobotLoader;

            // back compatibility to robot loader of version  < 3.0
            if (method_exists($robot, 'setCacheStorage')) {
                $robot->setCacheStorage(new Nette\Caching\Storages\DevNullStorage);
            }

            $robot->addDirectory($config['scanDirs']);
            $robot->acceptFiles = '*.php';
            $robot->rebuild();
            $classes = array_keys($robot->getIndexedClasses());
        }

        $repositories = [];
        foreach (array_unique($classes) as $class) {
            if (class_exists($class)
                && ($rc = new \ReflectionClass($class)) && $rc->isSubclassOf('LeanMapper\Repository')
                && !$rc->isAbstract()
            ) {
                $repositories[] = $rc->getName();
            }
        }
        return $repositories;
    }

}
