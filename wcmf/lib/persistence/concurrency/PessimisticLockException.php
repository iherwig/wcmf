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

use wcmf\lib\persistence\concurrency\Lock;

/**
 * @class PessimisticLockException
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PessimisticLockException extends \Exception {

  private $_lock = null;

  /**
   * Constructor
   * @param lock Lock instance that cause the exception
   */
  public function __construct(Lock $lock) {
    $this->_lock = $lock;

    parent::__construct("The object is currently locked by another user.");
  }

  /**
   * Get the lock
   * @return Lock instance
   */
  public function getLock() {
    return $this->_lock;
  }
}
?>
