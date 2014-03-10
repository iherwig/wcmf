<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */
namespace wcmf\application\controller;

use \Exception;

use wcmf\lib\core\Log;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;

/**
 * AssociateController is a controller that (dis-)associates Nodes
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
 * @param[in] role The role the target object should have in the source object
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AssociateController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();

    // check object id validity
    $sourceOid = ObjectId::parse($request->getValue('sourceOid'));
    if(!$sourceOid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('sourceOid')))));
      return false;
    }
    $targetOid = ObjectId::parse($request->getValue('targetOid'));
    if(!$targetOid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        array('invalidOids' => array($request->getValue('targetOid')))));
      return false;
    }

    // check association
    $mapper = ObjectFactory::getInstance('persistenceFacade')->getMapper($sourceOid->getType());
    // try role
    if ($request->hasValue('role')) {
      $relationDesc = $mapper->getRelation($request->getValue('role'));
      if ($relationDesc == null) {
        $response->addError(ApplicationError::get('ROLE_INVALID'));
        return false;
      }
    }
    // try type
    else {
      $relationDesc = $mapper->getRelation($targetOid->getType());
      if ($relationDesc == null) {
        $response->addError(ApplicationError::get('ASSOCIATION_INVALID'));
        return false;
      }
    }
    return true;
  }

  /**
   * (Dis-)Associate the Nodes.
   * @return True in every case.
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {
    $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
    $request = $this->getRequest();
    $response = $this->getResponse();

    $transaction = $persistenceFacade->getTransaction();
    $transaction->begin();
    try {
      // get the source node
      $sourceOID = ObjectId::parse($request->getValue('sourceOid'));
      $sourceNode = $persistenceFacade->load($sourceOID, BuildDepth::SINGLE);

      // get the target node
      $targetOID = ObjectId::parse($request->getValue('targetOid'));
      $targetNode = $persistenceFacade->load($targetOID, BuildDepth::SINGLE);

      // get the role
      $role = null;
      if ($request->hasValue('role')) {
        $role = $request->getValue('role');
      }

      if ($sourceNode != null && $targetNode != null) {
        // process actions
        if ($request->getAction() == 'associate') {
          try {
            $sourceNode->addNode($targetNode, $role);
          }
          catch (Exception $ex) {
            throw new ApplicationException($request, $response,
                    ApplicationError::get('ASSOCIATION_INVALID'));
          }
        }
        elseif ($request->getAction() == 'disassociate') {
          try {
            $sourceNode->deleteNode($targetNode, $role);
          }
          catch (Exception $ex) {
            throw new ApplicationException($request, $response,
                    ApplicationError::get('ASSOCIATION_INVALID'));
          }
        }
      }
      else {
        if ($sourceNode == null) {
          $response->addError(ApplicationError::get('OID_INVALID',
            array('invalidOids' => array('sourceOid'))));
        }
        if ($targetNode == null) {
          $response->addError(ApplicationError::get('OID_INVALID',
            array('invalidOids' => array('targetOid'))));
        }
      }
      $transaction->commit();
    }
    catch (Exception $ex) {
      Log::error($ex, __CLASS__);
      $response->addError(ApplicationError::fromException($ex));
      $transaction->rollback();
    }

    $response->setAction('ok');
    return true;
  }
}
?>