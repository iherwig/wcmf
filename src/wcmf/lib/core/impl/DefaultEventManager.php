<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\lib\core\impl;

use wcmf\lib\core\Event;
use wcmf\lib\core\EventManager;

/**
 * DefaultEventManager is a simple EventManager implementation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DefaultEventManager implements EventManager {

  private $_listeners = array();

  /**
   * @see EventManager::addListener()
   */
  public function addListener($eventName, $callback) {
    if (!isset($this->_listeners[$eventName])) {
      $this->_listeners[$eventName] = array();
    }
    $this->_listeners[$eventName][] = $callback;
  }

  /**
   * @see EventManager::removeListener()
   */
  public function removeListener($eventName, $callback) {
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
   * @see EventManager::dispatch()
   */
  public function dispatch($eventName, Event $event) {
    if (isset($this->_listeners[$eventName])) {
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
