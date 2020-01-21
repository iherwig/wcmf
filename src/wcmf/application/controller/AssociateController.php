<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use \Exception;

use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;

/**
 * AssociateController is used to (dis-)associates Node instances,
 * e.g. in a parent/child association.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ associate </div>
 * <div>
 * Connect two Node instances.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `sourceOid`      | The object id of the association's source Node
 * | _in_ `targetOid`      | The object id of the association's target Node
 * | _in_ `role`           | The role of the target Node in the source Node
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ disassociate </div>
 * <div>
 * Disconnect two Node instances.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `sourceOid`      | The object id of the association's source Node
 * | _in_ `targetOid`      | The object id of the association's target Node
 * | _in_ `role`           | The role of the target Node in the source Node
 * | __Response Actions__  | |
 * | `ok`                  | In all cases
 * </div>
 * </div>
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
        ['invalidOids' => [$request->getValue('sourceOid')]]));
      return false;
    }
    $targetOid = ObjectId::parse($request->getValue('targetOid'));
    if(!$targetOid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        ['invalidOids' => [$request->getValue('targetOid')]]));
      return false;
    }

    // check association
    $persistenceFacade = $this->getPersistenceFacade();
    $mapper = $persistenceFacade->getMapper($sourceOid->getType());
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
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $this->requireTransaction();
    $request = $this->getRequest();
    $response = $this->getResponse();
    $persistenceFacade = $this->getPersistenceFacade();

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
                ['invalidOids' => ['sourceOid']]));
      }
      if ($targetNode == null) {
        $response->addError(ApplicationError::get('OID_INVALID',
                ['invalidOids' => ['targetOid']]));
      }
    }

    $response->setAction('ok');
  }
}
?>