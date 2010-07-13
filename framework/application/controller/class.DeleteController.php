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
require_once(BASE."wcmf/lib/presentation/class.Controller.php");
require_once(BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(BASE."wcmf/lib/persistence/class.LockManager.php");
require_once(BASE."wcmf/lib/model/class.Node.php");

/**
 * @class DeleteController
 * @ingroup Controller
 * @brief DeleteController is a controller that delete Nodes.
 * 
 * <b>Input actions:</b>
 * - unspecified: Delete given Nodes
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 * 
 * @param[in] deleteoids A comma-separated string of the oids of the Nodes to delete.
 * @param[out] oids A comma-separated string of the oids of the deleted Nodes.
 * @param[out] poid The last parent oid of the last deleted Node.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DeleteController extends Controller
{
  /**
   * @see Controller::hasView()
   */
  function hasView()
  {
    return false;
  }
  /**
   * Delete given Nodes.
   * @return Array of given context and action 'ok' in every case.
   * @attention This controller always does a recursive delete
   * @see Controller::executeKernel()
   */
  function executeKernel()
  {
    $persistenceFacade = &PersistenceFacade::getInstance();    
    $lockManager = &LockManager::getInstance();
      
    // for deleting nodes we need not know the correct relations between the nodes
    // so we store the nodes to delete in an array and iterate over it when deleting
    $deleteArray = array();
    $removedOIDs = array();
    
    // start the persistence transaction
    $persistenceFacade->startTransaction();

    // load doomed children
    foreach(split(",", $this->_request->getValue('deleteoids')) as $doid)
    {
      // if the current user has a lock on the object, release it
      $lockManager->releaseLock($doid);

      // check if the object belonging to doid is locked and continue with next if so
      $lock = $lockManager->getLock($doid);
      if ($lock != null)
      {
        $this->appendErrorMsg($lockManager->getLockMessage($lock, $doid));
        continue;
      }

      $doomedChild = &$persistenceFacade->load($doid, BUILDDEPTH_SINGLE);
      if ($doomedChild != null)
      {
        if($this->confirmDelete($doomedChild))
        {
          $poids = $doomedChild->getParentOIDs();
          $removedOIDs[$doid] = $poids;
          $doomedChild->setState(STATE_DELETED);
          $deleteArray[sizeof($deleteArray)] = &$doomedChild;
        }
      }
      else
        Log::warn(Message::get("An object with oid %1% is does not exist.", array($doid)), __CLASS__);
    }

    // commit changes
    $localization = Localization::getInstance();
    for($i=0; $i<sizeof($deleteArray); $i++)
    {
      $curObj = &$deleteArray[$i];
      if ($this->isLocalizedRequest())
      {
        // delete the translations for the requested language
        $localization->deleteTranslation($curObj->getOID(), $this->_request->getValue('language'));
      }
      else
      {
        // delete the real object data and all translations
        $localization->deleteTranslation($curObj->getOID());
        $curObj->delete();
      }
    }
    
    // after delete
    $lastPOID = null;
    foreach($removedOIDs as $oid => $poids)
    {
      $this->afterDelete($oid, $poids);
      $lastPOID = $poids[sizeof($poids)-1];
    }
    
    // end the persistence transaction
    $persistenceFacade->commitTransaction();

    $this->_response->setValue('poid', $lastPOID);
    $this->_response->setValue('oids', array_keys($removedOIDs));
    $this->_response->setAction('ok');
    return true;
  }
  /**
   * Confirm delete action on given Node.
   * @note subclasses will override this to implement special application requirements.
   * @param node A reference to the Node to confirm.
   * @return True/False whether the Node should be deleted [default: true].
   */
  function confirmDelete(&$node)
  {
    return true;
  }
  /**
   * Called after delete.
   * @note subclasses will override this to implement special application requirements.
   * @param oid The oid of the Node deleted.
   * @param poids An array of oids of the Nodes from that the Node was deleted.
   * @note The method is called for all delete candidates even if they are not deleted.
   */
  function afterDelete($oid, $poids) {}
}
?>
