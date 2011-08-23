<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2009 wemove digital solutions GmbH
 *
 * Licensed under the terms of any of the following licenses
 * at your choice:
 *
 * - GNU Lesser General Public License (LGPL)
 *   http://www.gnu.org/licenses/lgpl.html
 * - Eclipse Public License (EPL)
 *   http://www.eclipse.org/org/documents/epl-v10.php
 *
 * See the license.txt file distributed with this work for
 * additional information.
 *
 * $Id$
 */
require_once(WCMF_BASE."wcmf/lib/core/Event.php");

/**
 * @class EventManager
 * @ingroup Event
 * @brief EventManager is responsible for dispatching events
 * to registered listeners.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class EventManager
{
  private static $_instance = null;
  private $_listeners = array();

  private function __construct() {}

  /**
   * Returns the only instance of the class.
   * @return EventManager instance
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance)) {
      self::$_instance = new EventManager();
    }
    return self::$_instance;
  }

  /**
   * Register a listener for a given event
   * @param eventName The event name
   * @param callback A php callback
   */
  public function addListener($eventName, $callback)
  {
    if (!isset($this->_listeners[$eventName])) {
      $this->_listeners[$eventName] = array();
    }
    $this->_listeners[$eventName][] = $callback;
  }
  /**
   * Remove a listener for a given event
   * @param eventName The event name
   * @param callback A php callback
   */
  public function removeListener($eventName, $callback)
  {
    if (isset($this->_listeners[$eventName])) {
      $listeners = array();
      for ($i=0, $count=sizeof($this->_listeners[$eventName]); $i<$count; $i++) {
        $curCallback = $this->_listeners[$eventName][$i];
        if ($curCallback != $callback) {
          $listeners[] = $curCallback;
        }
      }
      $this->_listeners[$eventName] = $listeners;
    }
  }
  /**
   * Notify listeners about the given event.
   * @param eventName The event name
   * @param event An Event instance
   */
  public function dispatch($eventName, Event $event)
  {
    if (isset($this->_listeners[$eventName]))
    {
      for ($i=0, $count=sizeof($this->_listeners[$eventName]); $i<$count; $i++) {
        $curCallback = $this->_listeners[$eventName][$i];
        call_user_func($curCallback, $event);
        if ($event->isStopped()) {
          break;
        }
      }
    }
  }
}
?>
