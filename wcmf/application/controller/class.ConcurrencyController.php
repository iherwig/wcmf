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
require_once(WCMF_BASE."wcmf/lib/presentation/class.Controller.php");
require_once(WCMF_BASE."wcmf/lib/persistence/locking/class.LockManager.php");
require_once(WCMF_BASE."wcmf/lib/security/class.RightsManager.php");

/**
 * @class ConcurrencyController
 * @ingroup Controller
 * @brief ConcurrencyController is a controller that allows to lock/unlock objects.
 *
 * <b>Input actions:</b>
 * - @em lock Lock an entity
 * - @em unlock Unlock an entity
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] oid The object id of the entity to lock/unlock
 * @param[out] oid The object id of the entity to lock/unlock
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ConcurrencyController extends Controller
{
  /**
   * @see Controller::validate()
   */
  function validate()
  {
    if(!PersistenceFacade::isValidOID($this->_request->getValue('oid')))
    {
      $this->setErrorMsg("No valid 'oid' given in data.");
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * (Un-)Lock the Node.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $lockManager = &LockManager::getInstance();
    $rightsManager = &RightsManager::getInstance();
    $session = &SessionData::getInstance();
    $oid = $this->_request->getValue('oid');

    // process actions
    if ($this->_request->getAction() == 'lock')
    {
      $lock = $lockManager->aquireLock($oid);
      $authUser = $rightsManager->getAuthUser();
      if ($lock && $authUser && ($lock->getLogin() != $authUser->getLogin() || $lock->getSessionID() != $session->getID()))
      {
        $this->appendErrorMsg($lockManager->getLockMessage($lock));
      }
    }
    elseif ($this->_request->getAction() == 'unlock')
    {
      $lockManager->releaseLock($oid);
    }

    $this->_response->setValue('oid', $oid);
    $this->_response->setAction('ok');
    return true;
  }
}
?>

