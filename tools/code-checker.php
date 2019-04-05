<?php

$autoload = __DIR__ . '/../temp/code-checker/vendor/autoload.php';

if (@!include $autoload) {
    echo "Install nette/code-checker using Composer into directory ../temp/code-checker, use\n";
    echo "composer create-project nette/code-checker temp/code-checker ^3\n";
    exit(1);
}

set_exception_handler(function (\Throwable $e) {
    echo "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n";
    die(2);
});

set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (($severity & error_reporting()) === $severity) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

set_time_limit(0);

$checker = new Nette\CodeChecker\Checker;
$tasks = Nette\CodeChecker\Tasks::class;

$checker->readOnly = true;
$checker->showProgress = false;

$checker->addTask([$tasks, 'controlCharactersChecker']);
$checker->addTask([$tasks, 'bomFixer']);
$checker->addTask([$tasks, 'utf8Checker']);
$checker->addTask([$tasks, 'phpSyntaxChecker'], '*.php,*.phpt');
$checker->addTask([$tasks, 'invalidPhpDocChecker'], '*.php,*.phpt');
$checker->addTask([$tasks, 'shortArraySyntaxFixer'], '*.php,*.phpt');

$checker->addTask([$tasks, 'newlineNormalizer'], '!*.sh');

$checker->addTask([$tasks, 'invalidDoubleQuotedStringChecker'], '*.php,*.phpt');
$checker->addTask([$tasks, 'trailingPhpTagRemover'], '*.php,*.phpt');
$checker->addTask([$tasks, 'neonSyntaxChecker'], '*.neon');
$checker->addTask([$tasks, 'jsonSyntaxChecker'], '*.json');
$checker->addTask([$tasks, 'yamlIndentationChecker'], '*.yml');
$checker->addTask([$tasks, 'trailingWhiteSpaceFixer']);
$checker->addTask([$tasks, 'tabIndentationChecker'], '*.json');
$checker->addTask([$tasks, 'yamlIndentationChecker'], '*.php,*.phpt');
$checker->addTask([$tasks, 'unexpectedTabsChecker'], '*.yml');

$ok = $checker->run(__DIR__ . '/../');

exit($ok ? 0 : 1);
