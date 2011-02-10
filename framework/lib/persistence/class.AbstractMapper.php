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
require_once(WCMF_BASE."wcmf/lib/util/class.Log.php");
require_once(WCMF_BASE."wcmf/lib/util/class.Message.php");
require_once(WCMF_BASE."wcmf/lib/security/class.RightsManager.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/security/class.AuthorizationException.php");

/**
 * @class AbstractMapper
 * @ingroup Persistence
 * @brief AbstractMapper provides a basic implementation for other mapper classes.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractMapper
{
  private $_dataConverter = null; // a DataConverter object that converts data before writing and after reading from storage
  private $_logging = false;
  private $_logStrategy = null;

  /**
   * @see PersistenceMapper::hasRelation()
   */
  public function hasRelation($roleName)
  {
    $relations = $this->getRelations();
    foreach ($relations as $relation) {
      if ($relation->otherRole == $roleName) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::hasAttribute()
   */
  public function hasAttribute($name)
  {
    $attributes = $this->getAttributes();
    foreach ($attributes as $attribute) {
      if ($attribute->name == $name) {
        return true;
      }
    }
    return false;
  }

  /**
   * @see PersistenceMapper::setDataConverter()
   */
  public function setDataConverter(DataConverter $dataConverter)
  {
    $this->_dataConverter = $dataConverter;
  }
  /**
   * @see PersistenceMapper::getDataConverter()
   */
  public function getDataConverter()
  {
    return $this->_dataConverter;
  }
  /**
   * @see PersistenceMapper::enableLogging()
   */
  public function enableLogging(OutputStrategy $logStrategy)
  {
    $this->_logStrategy = $logStrategy;
    $this->_logging = true;
  }
  /**
   * @see PersistenceMapper::disableLogging()
   */
  public function disableLogging()
  {
    $this->_logging = false;
  }
  /**
   * @see PersistenceMapper::isLogging()
   */
  public function isLogging()
  {
    return $this->_logging;
  }
  /**
   * @see PersistenceMapper::logAction()
   */
  public function logAction(PersistentObject $obj)
  {
    if ($this->isLogging()) {
      $this->_logStrategy->writeObject($obj);
    }
  }
  /**
   * @see PersistenceMapper::load()
   */
  public function load(ObjectId $oid, $buildDepth, array $buildAttribs=array(), array $buildTypes=array())
  {
    if (!$this->checkAuthorization($oid, ACTION_READ))
    {
      $this->authorizationFailedError($oid, ACTION_READ);
      return;
    }

    if (!ObjectId::isValid($oid)) {
      return null;
    }
    // load object
    $object = $this->loadImpl($oid, $buildDepth, $buildAttribs, $buildTypes);
    if ($object != null)
    {
      // set immutable if not authorized for modification
      if (!$this->checkAuthorization($oid, ACTION_MODIFY)) {
        $object->setImmutable();
      }
      $this->initialize($object);

      // call custom initialization
      $object->afterLoad();
    }
    return $object;
  }
  /**
   * @see PersistenceMapper::create()
   */
  public function create($type, $buildDepth=BUILDDEPTH_SINGLE, array $buildAttribs=array())
  {
    // Don't check rights here, because object creation may be needed
    // for internal purposes. That newly created objects may not be saved
    // to the storage is asured by the save method.
    $object = $this->createImpl($type, $buildDepth, $buildAttribs);

    $this->initialize($object);

    // call custom initialization
    $object->afterCreate();

    return $object;
  }
  /**
   * @see PersistenceMapper::save()
   */
  public function save(PersistentObject $object)
  {
    if ( ($object->getState() == STATE_DIRTY) && !$this->checkAuthorization($object->getOID(), ACTION_MODIFY) )
    {
      $this->authorizationFailedError($object->getOID(), ACTION_MODIFY);
      return;
    }
    else if ( ($object->getState() == STATE_NEW) && !$this->checkAuthorization($object->getOID(), ACTION_CREATE) )
    {
      $this->authorizationFailedError($object->getOID(), ACTION_CREATE);
      return;
    }

    // modify object
    return $this->saveImpl($object);
  }
  /**
   * @see PersistenceMapper::delete()
   */
  public function delete(ObjectId $oid, $recursive=true)
  {
    if (!$this->checkAuthorization($oid, ACTION_DELETE))
    {
      $this->authorizationFailedError($oid, ACTION_DELETE);
      return;
    }

    if (!ObjectId::isValid($oid)) {
      return false;
    }
    // delete oid
    $result = $this->deleteImpl($oid, $recursive);
    if ($result === true)
    {
      // release any locks on the object
      $lockManager = LockManager::getInstance();
      $lockManager->releaseLocks($oid);
    }
    return $result;
  }
  /**
   * Check authorization on an type/OID and a given action.
   * @param oid The object id of the Object to authorize (its type will be checked too)
   * @param action Action to authorize
   * @return True/False depending on success of authorization
   */
  protected function checkAuthorization(ObjectId $oid, $action)
  {
    $rightsManager = RightsManager::getInstance();
    if (!$rightsManager->authorize($oid, '', $action)) {
      return false;
    }
    else {
      return true;
    }
  }
  protected function authorizationFailedError(ObjectId $oid, $action)
  {
    // when reading only log the error to avoid errors on the display
    $msg = Message::get("Authorization failed for action '%1%' on '%2%'.", array($action, $oid));
    if ($action == ACTION_READ) {
      Log::error($msg."\n".Application::getStackTrace(), __CLASS__);
    }
    else {
      throw new AuthorizationException($msg);
    }
  }
  /**
   * Initialize the object after creation/loading and before handing it over to the application.
   * @note Subclasses may override this to implement special requirements (e.g. install listeners).
   * Remember to always call parent::initialize().
   * @param object A reference to the object
   */
  protected function initialize(PersistenceFacade $object) {}

  /**
   * @see PersistenceFacade::load()
   * @note Precondition: Object rights have been checked already
   *
   */
  abstract protected function loadImpl(ObjectId $oid, $buildDepth, array $buildAttribs=null, array $buildTypes=null);
  /**
   * @see PersistenceFacade::create()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function createImpl($type, $buildDepth=BUILDDEPTH_SINGLE, array $buildAttribs=null);
  /**
   * @see PersistenceFacade::save()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function saveImpl(PersistentObject $object);
  /**
   * @see PersistenceFacade::delete()
   * @note Precondition: Object rights have been checked already
   */
  abstract protected function deleteImpl(ObjectId $oid, $recursive=true);

  /**
   * @see PersistenceFacade::startTransaction()
   * @note The default implementation does nothing. Subclasses may override this method if required
   */
  public function startTransaction() {}
  /**
   * @see PersistenceFacade::commitTransaction()
   * @note The default implementation does nothing. Subclasses may override this method if required
   */
  public function commitTransaction() {}
  /**
   * @see PersistenceFacade::rollbackTransaction()
   * @note The default implementation does nothing. Subclasses may override this method if required
   */
  public function rollbackTransaction() {}
}
?>
