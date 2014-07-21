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
 * PermissionsController is a controller that handles permissions.
 *
 * <b>Input actions:</b>
 * - checkpermissions: Check a set of resource/context/action triples
 *
 * <b>Output actions:</b>
 * - @em ok In any case
 *
 * @param[in] permissions Array of resource/context/action triples in the form resource?context?action
 * @param[out] result Associative array with the permissions as keys and boolean values indicating
 *            if permissions are given or not
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionsController extends Controller {

  /**
   * Check the permissions.
   * @return True in any case
   * @see Controller::executeKernel()
   */
  protected function executeKernel() {

    $request = $this->getRequest();
    $response = $this->getResponse();
    $permissionManager = ObjectFactory::getInstance('permissionManager');

    $permissions = $request->getValue('permissions');
    $result = array();
    foreach($permissions as $permission) {
      $keyParts = ActionKey::parseKey($permission);
      $result[$permission] = $permissionManager->authorize($keyParts['resource'], $keyParts['context'], $keyParts['action']);
    }
    $response->setValue('result', $result);

    $response->setAction('ok');
    return true;
  }
}
?>