<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\config\ActionKey;
use wcmf\lib\core\ObjectFactory;
use wcmf\lib\presentation\Controller;

/**
 * PermissionsController checks permissions for a set of operations for
 * the current user.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ _default_ </div>
 * <div>
 * Check a set of operations.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `operations`      | Array of resource/context/action triples in the form _resource?context?action_
 * | _out_ `result`         | Associative array with the operations as keys and boolean values indicating if permissions are given or not
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionsController extends Controller {

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {

    $request = $this->getRequest();
    $response = $this->getResponse();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $result = array();
    $permissions = $request->hasValue('operations') ? $request->getValue('operations') : array();
    foreach($permissions as $permission) {
      $keyParts = ActionKey::parseKey($permission);
      $result[$permission] = $permissionManager->authorize($keyParts['resource'], $keyParts['context'], $keyParts['action']);
    }
    $response->setValue('result', $result);

    $response->setAction('ok');
  }
}
?>