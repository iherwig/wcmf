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

/**
 * @class Lock
 * @ingroup Persistence
 * @brief Lock represents a lock on an object.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Lock
{
  var $_oid = "";
  var $_useroid = "";
  var $_login = "";
  var $_sessid = "";
  var $_created = "";

  /**
   * Creates a lock on a given object.
   * @param oid The oid of the object to lock
   * @param useroid The oid of the user who holds the lock
   * @param login The login of the user who holds the lock
   * @param sessid The id of the session of the user
   * @param created The creation date of the lock. If omitted the current date will be taken.
   */  
  function Lock($oid, $useroid, $login, $sessid, $created='')
  {
    $this->_oid = $oid;
    $this->_useroid = $useroid;
    $this->_login = $login;
    $this->_sessid = $sessid;
    if ($created == '')
      $this->_created = date("Y-m-d H:i:s");
    else
      $this->_created = $created;
  }

  /**
   * Get the oid of the locked object.
   * @return The oid of the locked object.
   */  
  function getOID()
  {
    return $this->_oid;
  }

  /**
   * Get the oid of the user who holds the lock.
   * @return The oid of the user.
   */  
  function getUserOID()
  {
    return $this->_useroid;
  }

  /**
   * Get the login of the user who holds the lock.
   * @return The login of the user.
   */  
  function getLogin()
  {
    return $this->_login;
  }

  /**
   * Get the session id of the user who holds the lock.
   * @return The session id of the user.
   */  
  function getSessionID()
  {
    return $this->_sessid;
  }

  /**
   * Get the creation date/time of the lock.
   * @return The creation date/time of the lock.
   */  
  function getCreated()
  {
    return $this->_created;
  }
}
?>
