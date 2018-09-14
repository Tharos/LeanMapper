<?php

/**
 * This file is part of the Lean Mapper library (http://www.leanmapper.com)
 *
 * Copyright (c) 2013 Vojtěch Kohout (aka Tharos)
 *
 * For the full copyright and license information, please view the file
 * license.md that was distributed with this source code.
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
    protected $events = [
        self::EVENT_BEFORE_PERSIST => [],
        self::EVENT_BEFORE_CREATE => [],
        self::EVENT_BEFORE_UPDATE => [],
        self::EVENT_BEFORE_DELETE => [],
        self::EVENT_AFTER_PERSIST => [],
        self::EVENT_AFTER_CREATE => [],
        self::EVENT_AFTER_UPDATE => [],
        self::EVENT_AFTER_DELETE => [],
    ];



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
    protected function checkEventType($event)
    {
        if (!isset($this->events[$event])) {
            throw new InvalidArgumentException("Unknown event type given: '$event'.");
        }
    }

}
