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
require_once(WCMF_BASE."wcmf/lib/persistence/class.PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/class.Node.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NodeUtil.php");
require_once(WCMF_BASE."wcmf/lib/model/class.NullNode.php");

/**
 * @class AssociateController
 * @ingroup Controller
 * @brief AssociateController is a controller that (dis-)associates Nodes
 * (by setting the parent/child relations).
 *
 * <b>Input actions:</b>
 * - @em associate Associate one Node to another
 * - @em disassociate Disassociate one Node from another
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] sourceOid The object id of the object to originate the association from
 * @param[in] targetOid The object id of the destination object
 * @param[in] roleobject The role the target object should have in the source object
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AssociateController extends Controller
{
  /**
   * @see Controller::validate()
   */
  protected function validate()
  {
    $request = $this->getRequest();
    $response = $this->getResponse();
    if(!$request->hasValue('sourceOid') || (ObjectId::parse($request->getValue('sourceOid')) == null))
    {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('sourceOid'))));
      return false;
    }
    if(!$request->hasValue('targetOid') || (ObjectId::parse($request->getValue('targetOid')) == null))
    {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => array('targetOid'))));
      return false;
    }
    return true;
  }
  /**
   * @see Controller::hasView()
   */
  public function hasView()
  {
    return false;
  }
  /**
   * (Dis-)Associate the Nodes.
   * @return Array of given context and action 'ok' in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel()
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $lockManager = LockManager::getInstance();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // get the source node
    $sourceOID = ObjectId::parse($request->getValue('sourceOid'));
    $lockManager->releaseLock($sourceOID);
    $sourceNode = $persistenceFacade->load($sourceOID, BUILDDEPTH_SINGLE);

    // get the target node
    $targetOID = ObjectId::parse($request->getValue('targetOid'));
    $lockManager->releaseLock($targetOID);
    $targetNode = $persistenceFacade->load($targetOID, BUILDDEPTH_SINGLE);

    // get the role
    $role = null;
    if ($request->hasValue('role')) {
      $role = $request->getValue('role');
    }
    
    if ($sourceNode != null && $targetNode != null)
    {
      // process actions
      if ($request->getAction() == 'associate') {
        $sourceNode->addNode($targetNode, $role);
      }
      elseif ($request->getAction() == 'disassociate') {
        $sourceNode->deleteNode($targetNode, $role);
      }
      $sourceNode->save();
      $targetNode->save();
    }
    else
    {
      if ($sourceNode == null) {
        $this->appendErrorMsg(Message::get("Cannot %1% %2% and %3%. Source node does not exist.", 
                array($request->getAction(), $sourceOID, $targetOID)));
      }
      if ($targetNode == null) {
        $this->appendErrorMsg(Message::get("Cannot %1% %2% and %3%. Target node does not exist.", 
                array($request->getAction(), $sourceOID, $targetOID)));
      }
    }
    $response->setAction('ok');
    return true;
  }
}
?>