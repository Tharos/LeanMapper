<?php

declare(strict_types=1);

namespace LeanMapper\Bridges\Nette\DI;

use LeanMapper;
use Nette;
use Nette\Loaders\RobotLoader;
use Tracy;

class LeanMapperExtension extends Nette\DI\CompilerExtension
{

    /** @var array<string, mixed> */
    public $defaults = [
        'db' => [],
        'profiler' => true,
        'scanDirs' => null,
        'logFile' => null,
    ];


    /**
     * @param string[]|string|null $scanDirs
     */
    public function __construct($scanDirs = null)
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
            ->setFactory(LeanMapper\DefaultMapper::class);

        $container->addDefinition($this->prefix('entityFactory'))
            ->setFactory(LeanMapper\DefaultEntityFactory::class);

        $connection = $container->addDefinition($this->prefix('connection'))
            ->setFactory(LeanMapper\Connection::class, [$config['db']]);

        if (isset($config['db']['flags'])) {
            $flags = 0;
            foreach ((array)$config['db']['flags'] as $flag) {
                $flags |= constant($flag);
            }
            $config['db']['flags'] = $flags;
        }

        if (class_exists(Tracy\Debugger::class) && $container->parameters['debugMode'] && $config['profiler']) {
            $panel = $container->addDefinition($this->prefix('panel'))->setFactory(\Dibi\Bridges\Tracy\Panel::class);
            $connection->addSetup([$panel, 'register'], [$connection]);
            if ($config['logFile']) {
                $fileLogger = $container->addDefinition($this->prefix('fileLogger'))
                    ->setFactory(\Dibi\Loggers\FileLogger::class, [$config['logFile']]);
                $connection->addSetup('$service->onEvent[] = ?', [
                    [$fileLogger, 'logEvent'],
                ]);
            }
        }
    }


    /**
     * @param  array<string, mixed> $config
     * @return array<class-string>
     */
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
            $robot->acceptFiles = ['*.php'];
            $robot->rebuild();
            $classes = array_keys($robot->getIndexedClasses());
        }

        $repositories = [];
        foreach (array_unique($classes) as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $rc = new \ReflectionClass($class);

            if ($rc->isSubclassOf(\LeanMapper\Repository::class) && !$rc->isAbstract()) {
                $repositories[] = $rc->getName();
            }
        }
        return $repositories;
    }

}
