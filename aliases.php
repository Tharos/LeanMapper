<?php

/**
 * @author Vojtěch Kohout
 */

use Nette\Diagnostics\Debugger;
use Nette\Utils\Strings;

require __DIR__ . '/nette.min.php';
require __DIR__ . '/vendor/autoload.php';

Debugger::enable();
Debugger::$strictMode = true;

$source = file_get_contents(__DIR__ . '/_local/Author.php');

class Aliases
{

	private $aliases;

	private $current = '';

	private $last = '';


	public function resetCurrent()
	{
		$this->current = $this->last = '';
	}

	public function appendCurrent($name)
	{
		$this->current .= '\\' . $name;
		$this->last = $name;
	}

	public function setLast($name)
	{
		$this->last = $name;
	}

	public function finishCurrent()
	{
		$this->aliases[$this->last] = $this->current;
		$this->resetCurrent();
	}

	public function getAll()
	{
		return $this->aliases;
	}

}

$aliases = new Aliases;

$states = array(
	0 => function ($token) use ($aliases) { // initial state, waiting for use to come
		if (is_array($token) and $token[0] === T_USE) {
			$aliases->resetCurrent();
			return 1;
		}
		return 0;
	},
	1 => function ($token) use ($aliases) { // in use statement
		if (is_array($token)) {
			if ($token[0] === T_STRING) {
				$aliases->appendCurrent($token[1]);
			} elseif ($token[0] === T_AS) {
				return 2;
			}
		} else {
			if ($token === ';') {
				$aliases->finishCurrent();
				return 0;
			} elseif ($token === ',') {
				$aliases->finishCurrent();
			}
		}
		return 1;
	},
	2 => function ($token) use ($aliases) { // in as statement
		if (is_array($token)) {
			if ($token[0] === T_STRING) {
				$aliases->setLast($token[1]);
				$aliases->finishCurrent();
				return 3;
			}
		}
		return 2;
	},
	3 => function ($token) use ($aliases) { // right after finish of use statement
		if ($token === ';') {
			return 0;
		}
		return 1;
	}
);

$state = $states[0];
foreach (token_get_all($source) as $token) {
	$state = $states[$state($token)];
	if (is_array($token)) {
		$token[3] = token_name($token[0]);
	}
}

dump($aliases->getAll());