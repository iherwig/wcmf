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
namespace wcmf\lib\core;

/**
 * Event is the base class for all events.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class Event {

  private $isStopped = false;

  /**
   * Stop further processing of the event
   */
  public function stopPropagation(): void {
    $this->isStopped = true;
  }

  /**
   * Check if the event is stopped
   * @return bool
   */
  public function isStopped(): bool {
    return $this->isStopped;
  }
}
?>
