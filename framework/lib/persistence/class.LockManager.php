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
require_once(BASE."wcmf/lib/util/class.Message.php");
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/lib/util/class.SessionData.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.ILockHandler.php");
require_once(BASE."wcmf/lib/persistence/class.Lock.php");
require_once(BASE."wcmf/lib/security/class.RightsManager.php");
require_once(BASE."wcmf/lib/util/class.ObjectFactory.php");

/**
 * @class LockManager
 * @ingroup Persistence
 * @brief LockManager is used to handle lock requests on objects.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class LockManager
{
  private static $_instance = null;
  private function __construct() {}

  private $_impl = null;

  /**
   * Returns an instance of the class.
   * @return A reference to the only instance of the Singleton object
   */
  public static function getInstance()
  {
    if (!isset(self::$_instance) )
    {
      self::$_instance = new LockManager();
      
      $parser = InifileParser::getInstance();
      $locking = $parser->getValue('locking', 'cms');
      if ($locking)
      {
        // if the application runs in anonymous mode, locking is not supported
        $anonymous = $parser->getValue('anonymous', 'cms');
        if ($anonymous)
        {
          require_once(BASE."wcmf/lib/persistence/class.NullLockHandler.php");
          self::$_instance->_impl = new NullLockHandler();
        }
        else
        {
          $impl = ObjectFactory::createInstanceFromConfig('implementation', 'LockHandler');
          if ($impl === null || !($impl instanceof ILockHandler)) {
            throw new ConfigurationException($objectFactory->getErrorMsg());
          }
          self::$_instance->_impl = $impl;
        }
      }
      else {
        self::$_instance->_impl = new NullLockHandler();
      }
    }
    return self::$_instance;
  }
  /**
   * Lock a persistent object if it is not locked already and the current user
   * is allowed to modify it.
   * @param object The object to lock
   * @param name The display name of the object
   * @return A message describing the problem if locking is not possible
   *         because another user holds the lock
   */
  public static function handleLocking($object, $name)
  {
    $lockMsg = "";

    if ($object instanceof PersistentObject)
    {
      $lockManager = LockManager::getInstance();
      $rightsManager = RightsManager::getInstance();
      // check if we can edit the object
      if ($rightsManager->authorize($object->getOID(), '', ACTION_MODIFY))
      {
        // if object is locked by another user we retrieve the lock to show a message
        $lock = $object->getLock();
        if ($lock != null) {
          $lockMsg .= $lockManager->getLockMessage($lock, $name).'<br />';
        }
        else
        {
          // try to lock object
          $lockManager->aquireLock($object->getOID());
        }
      }
    }
    return $lockMsg;
  }
  /**
   * Aquire a lock on an ObjectId for the current user.
   * @param oid The object id of the object to lock.
   * @return True if successfull/False in case of an invalid oid or a Lock instance in case of an existing lock.
   */
  public function aquireLock(ObjectId $oid)
  {
    if (!ObjectId::isValid($oid)) {
      return false;
    }
    $lock = $this->getLock($oid);
    if ($lock === null)
    {
      $session = SessionData::getInstance();
      $authUser = $this->getUser();
      if ($authUser != null)
      {
        $lock = new Lock($oid, $authUser->getOID(), $authUser->getLogin(), $session->getID());
        $this->_impl->aquireLock($authUser->getOID(), $session->getID(), $oid, $lock->getCreated());
      }
    }
  	return $lock;
  }
  /**
   * Release a lock on an ObjectId for the current user.
   * @param oid object id of the object to release.
   */
  public function releaseLock(ObjectId $oid)
  {
    if (!ObjectId::isValid($oid)) {
      return false;
    }
    $session = SessionData::getInstance();
    $authUser = $this->getUser();
    if ($authUser != null) {
      $this->_impl->releaseLock($authUser->getOID(), $session->getID(), $oid);
    }
  }
  /**
   * Release all locks on an ObjectId regardless of the user.
   * @param oid object id of the object to release.
   */
  public function releaseLocks(ObjectId $oid)
  {
    if (!ObjectId::isValid($oid)) {
      return false;
    }
    $this->_impl->releaseLock(null, null, $oid);
  }
  /**
   * Release all lock for the current user.
   */
  public function releaseAllLocks()
  {
    $session = SessionData::getInstance();
    $authUser = $this->getUser();
    if ($authUser != null) {
      $this->_impl->releaseAllLocks($authUser->getOID(), $session->getID());
    }
  }
  /**
   * Get the default lock message for a lock.
   * @param lock The Lock instance to construct the message for.
   * @param objectText The display text for the locked object.
   * @return The lock message of the form 'objectText is locked by user 'admin' since 12:12:36<br />'.
   */
  public static function getLockMessage($lock, $objectText)
  {
    if ($objectText == '') {
      $objectText = $lock->getOID()->__toString();
    }
    $msg = Message::get("%1% is locked by user '%2%' since %3%. ", array($objectText, $lock->getLogin(), strftime("%X", strtotime($lock->getCreated()))));
    return $msg;
  }
  /**
   * Get lock data for an ObjectId. This method may also be used to check for an lock.
   * @note The method uses the php parameter 'session.gc_maxlifetime' to determine if a lock belongs to an expired session.
   * A lock with a creation date older than 'session.gc_maxlifetime' seconds is regarded to belong to an expired
   * session which results in the removal of all locks attached to that session.
   * @param oid object id of the object to get the lock data for.
   * @return A Lock instance or null if no lock exists/or in case of an invalid oid.
   */
  public function getLock(ObjectId $oid)
  {
    if (!ObjectId::isValid($oid)) {
      return null;
    }
    $lock = $this->_impl->getLock($oid);

    // remove lock if session is expired
    if ($lock != null)
    {
      $lifeTimeInSeconds = (mktime() - strtotime($lock->getCreated()));
      if ($lifeTimeInSeconds > ini_get("session.gc_maxlifetime"))
      {
        $this->_impl->releaseLock($lock->getUserOID(), $lock->getSessionID(), null);
        $lock = null;
      }
    }
    return $lock;
  }
  /**
   * Get the currently logged in user.
   * @return An instance of AuthUser.
   */
  protected function getUser()
  {
    $rightsManager = RightsManager::getInstance();
    return $rightsManager->getAuthUser();
  }
}
?>
