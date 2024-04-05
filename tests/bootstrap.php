<?php

declare(strict_types=1);

use LeanMapper\Connection;
use LeanMapper\DefaultEntityFactory;
use LeanMapper\DefaultMapper;

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


function test(string $title, Closure $function): void
{
    $function();
}


class Tests
{
    private function __construct()
    {
    }


    public static function getTempDirectory(): string
    {
        $tempDir = __DIR__ . '/tmp/' . getmypid();
        @mkdir($tempDir, 0777, true);
        Tester\Helpers::purge($tempDir);
        return $tempDir;
    }


    public static function createConnection(): Connection
    {
        $tempDir = self::getTempDirectory();

        if (!copy(__DIR__ . '/db/library-ref.sq3', $tempDir . '/library.sq3')) {
            echo 'Failed to copy SQLite database';
            exit(1);
        }

        return new Connection([
            'driver' => 'sqlite3',
            'database' => $tempDir . '/library.sq3',
        ]);
    }


    public static function createMapper(): \LeanMapper\IMapper
    {
        return new DefaultMapper(null);
    }


    public static function createEntityFactory(): \LeanMapper\IEntityFactory
    {
        return new DefaultEntityFactory;
    }
}
