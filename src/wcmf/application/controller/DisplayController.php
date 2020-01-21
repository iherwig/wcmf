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

use wcmf\lib\model\NodeUtil;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\ObjectId;
use wcmf\lib\persistence\PersistenceAction;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\ApplicationException;
use wcmf\lib\presentation\Controller;

/**
 * DisplayController is used to read a Node instance.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Load the given Node instance.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `oid`             | The object id of the Node to read
 * | _in_ `depth`           | The number of levels referenced Node must be returned as complete objects. Below this level, Nodes are returned as references. The value -1 has the special meaning of unlimited depth (optional, default: 1)
 * | _in_ `useDefaults`     | Boolean whether to apply values from the default language, if they are not provided in a translation (optional, default: _true_)
 * | _in_ `translateValues` | Boolean whether list values should be translated to their display values (optional, default: _false_)
 * | _out_ `object`         | The Node to read
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class DisplayController extends Controller {

  /**
   * @see Controller::validate()
   */
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $oid = ObjectId::parse($request->getValue('oid'));
    if(!$oid) {
      $response->addError(ApplicationError::get('OID_INVALID',
        ['invalidOids' => [$request->getValue('oid')]]));
      return false;
    }
    if($request->hasValue('depth')) {
      $depth = intval($request->getValue('depth'));
      if ($depth < -1) {
        $response->addError(ApplicationError::get('DEPTH_INVALID'));
      }
    }
    if (!$this->checkLanguageParameter()) {
      return false;
    }
    // do default validation
    return parent::validate();
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $persistenceFacade = $this->getPersistenceFacade();
    $permissionManager = $this->getPermissionManager();
    $request = $this->getRequest();
    $response = $this->getResponse();

    // check permission
    $oid = ObjectId::parse($request->getValue('oid'));
    if (!$permissionManager->authorize($oid, '', PersistenceAction::READ)) {
      throw new ApplicationException($request, $response, ApplicationError::get('PERMISSION_DENIED'));
    }

    // load model
    $buildDepth = $request->getValue('depth', BuildDepth::SINGLE);
    $node = $persistenceFacade->load($oid, $buildDepth);
    if ($node == null) {
      $response->setStatus(404);
      return;
    }

    // translate all nodes to the requested language if requested
    if ($this->isLocalizedRequest()) {
      $localization = $this->getLocalization();
      $node = $localization->loadTranslation($node, $request->getValue('language'), $request->getBooleanValue('useDefaults', true), true);
    }

    // translate values if requested
    if ($request->getBooleanValue('translateValues')) {
      $nodes = [$node];
      if ($this->isLocalizedRequest()) {
        NodeUtil::translateValues($nodes, $request->getValue('language'));
      }
      else {
        NodeUtil::translateValues($nodes);
      }
    }

    // assign node data
    $response->setValue('object', $node);

    // success
    $response->setAction('ok');
  }
}
?>
