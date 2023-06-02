<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
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

  private $listeners = [];

  /**
   * @see EventManager::addListener()
   */
  public function addListener(string $eventName, callable $callback): void {
    if (!isset($this->listeners[$eventName])) {
      $this->listeners[$eventName] = [];
    }
    $this->listeners[$eventName][] = $callback;
  }

  /**
   * @see EventManager::removeListener()
   */
  public function removeListener(string $eventName, callable $callback): void {
    if (isset($this->listeners[$eventName])) {
      $listeners = [];
      for ($i=0, $count=sizeof($this->listeners[$eventName]); $i<$count; $i++) {
        $curCallback = $this->listeners[$eventName][$i];
        if ($curCallback != $callback) {
          $listeners[] = $curCallback;
        }
      }
      $this->listeners[$eventName] = $listeners;
    }
  }

  /**
   * @see EventManager::dispatch()
   */
  public function dispatch(string $eventName, Event $event): void {
    if (isset($this->listeners[$eventName])) {
      for ($i=0, $count=sizeof($this->listeners[$eventName]); $i<$count; $i++) {
        $curCallback = $this->listeners[$eventName][$i];
        call_user_func($curCallback, $event);
        if ($event->isStopped()) {
          break;
        }
      }
    }
  }
}
?>
