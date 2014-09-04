<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\concurrency;

/**
 * PersistentLock defines the interface for locks that may be persisted
 * (e.g. for pessimistic offline locking).
 *
 * @author ingo herwig <ingo@wemove.com>
 */
interface PersistentLock {

  /**
   * Get the object id of the locked object.
   * @return ObjectId of the locked object.
   */
  public function getObjectId();

  /**
   * Get the login of the user who holds the lock.
   * @return The login of the user.
   */
  public function getLogin();

  /**
   * Get the creation date/time of the lock.
   * @return The creation date/time of the lock.
   */
  public function getCreated();
}
?>
