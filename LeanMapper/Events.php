<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license-mit.txt that was distributed with this source code.
 */

namespace LeanMapper;

use LeanMapper\Exception\InvalidArgumentException;

/**
 * @author Vojtěch Kohout
 */
class Events
{

	const EVENT_BEFORE_PERSIST = 'beforePersist';

	const EVENT_BEFORE_CREATE = 'beforeCreate';

	const EVENT_BEFORE_UPDATE = 'beforeUpdate';

	const EVENT_BEFORE_DELETE = 'beforeDelete';

	const EVENT_AFTER_PERSIST = 'afterPersist';

	const EVENT_AFTER_CREATE = 'afterCreate';

	const EVENT_AFTER_UPDATE = 'afterUpdate';

	const EVENT_AFTER_DELETE = 'afterDelete';

	/** @var array */
	private $events = array(
		self::EVENT_BEFORE_PERSIST => array(),
		self::EVENT_BEFORE_CREATE => array(),
		self::EVENT_BEFORE_UPDATE => array(),
		self::EVENT_BEFORE_DELETE => array(),
		self::EVENT_AFTER_PERSIST => array(),
		self::EVENT_AFTER_CREATE => array(),
		self::EVENT_AFTER_UPDATE => array(),
		self::EVENT_AFTER_DELETE => array(),
	);


	/**
	 * Registers new callback for given event
	 *
	 * @param string $event
	 * @param mixed $callback
	 */
	public function registerCallback($event, $callback)
	{
		$this->checkEventType($event);
		$this->events[$event][] = $callback;
	}

	/**
	 * Invokes callbacks registered for given event
	 *
	 * @param string $event
	 * @param mixed $arg
	 * @throws InvalidArgumentException
	 */
	public function invokeCallbacks($event, $arg)
	{
		$this->checkEventType($event);
		foreach ($this->events[$event] as $callback) {
			call_user_func($callback, $arg);
		}
	}

	/**
	 * Gets reference to array of registered events
	 *
	 * @param string $event
	 * @return array
	 */
	public function &getCallbacksReference($event)
	{
		$this->checkEventType($event);
		return $this->events[$event];
	}

	//////////////////////
	//////////////////////

	/**
	 * @param string $event
	 * @throws InvalidArgumentException
	 */
	private function checkEventType($event)
	{
		if (!isset($this->events[$event])) {
			throw new InvalidArgumentException("Unknown event type given: '$event'.");
		}
	}

}
