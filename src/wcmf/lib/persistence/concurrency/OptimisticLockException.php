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
namespace wcmf\lib\persistence\concurrency;

/**
 * @class OptimisticLockException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class OptimisticLockException extends \Exception {

  private $_currentState = null;

  /**
   * Constructor
   * @param currentState PersistentObject instance representing the current object state
   *    or null, if the object is deleted
   */
  public function __construct($currentState) {
    $this->_currentState = $currentState;

    $msg = '';
    if ($currentState == null) {
      $msg = 'The object was deleted by another user.';
    }
    else {
      $msg = 'The object was modified by another user.';
    }
    parent::__construct($msg);
  }

  /**
   * Get the current object
   * @return PersistentObject instance
   */
  public function getCurrentState() {
    return $this->_currentState;
  }
}
?>
