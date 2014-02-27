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
namespace wcmf\lib\core;

use wcmf\lib\core\Event;

/**
 * EventManager is responsible for dispatching events
 * to registered listeners.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface EventManager {

  /**
   * Register a listener for a given event
   * @param eventName The event name
   * @param callback A php callback
   */
  public function addListener($eventName, $callback);

  /**
   * Remove a listener for a given event
   * @param eventName The event name
   * @param callback A php callback
   */
  public function removeListener($eventName, $callback);

  /**
   * Notify listeners about the given event.
   * @param eventName The event name
   * @param event An Event instance
   */
  public function dispatch($eventName, Event $event);
}
?>
