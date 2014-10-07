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
 * PermissionController checks permissions for a set of operations for
 * the current user.
 *
 * The controller supports the following actions:
 *
 * <div class="controller-action">
 * <div> __Action__ checkPermissions </div>
 * <div>
 * Check permissions of a set of operations for the current user.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `operations`      | Array of resource/context/action triples in the form _resource?context?action_
 * | _out_ `result`         | Associative array with the operations as keys and boolean values indicating if permissions are given or not
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ checkPermissionsOfUser </div>
 * <div>
 * Check permissions of a set of operations for the given user.
 * | Parameter              | Description
 * |------------------------|-------------------------
 * | _in_ `operations`      | Array of resource/context/action triples in the form _resource?context?action_
 * | _in_ `user`            | Username to check permissions for (optional, default: the authenticated user)
 * | _out_ `result`         | Associative array with the operations as keys and boolean values indicating if permissions are given or not
 * | __Response Actions__   | |
 * | `ok`                   | In all cases
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ createPermission </div>
 * <div>
 * Create/Change a permission for a role on a resource, context, action combination.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `resource`       | The resource (e.g. class name of the Controller or ObjectId).
 * | _in_ `context`        | The context in which the action takes place (optional).
 * | _in_ `action`         | The action to process.
 * | _in_ `role`           | The role to add.
 * | _in_ `modifier`       | _+_ or _-_ whether to allow or disallow the action for the role.
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ removePermission </div>
 * <div>
 * Remove a role from a permission on a resource, context, action combination.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `resource`       | The resource (e.g. class name of the Controller or ObjectId).
 * | _in_ `context`        | The context in which the action takes place (optional).
 * | _in_ `action`         | The action to process.
 * | _in_ `role`           | The role to remove.
 * </div>
 * </div>
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class PermissionController extends Controller {

  /**
   * @see Controller::validate()
   */
  function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $invalidParameters = array();
    if ($request->getAction() == 'createPermission' || $request->getAction() == 'removePermission') {
      foreach (array('resource', 'context', 'action') as $param) {
        if(!$request->hasValue($param)) {
          $invalidParameters[] = $param;
        }
      }
    }
    if ($request->getAction() == 'createPermission') {
      $modifier = $request->getValue('modifier');
      if (!($modifier == PermissionManager::PERMISSION_MODIFIER_ALLOW ||
              $modifier == PermissionManager::PERMISSION_MODIFIER_DENY)) {
        $invalidParameters[] = 'modifier';
      }
    }

    if (sizeof($invalidParameters) > 0) {
      $response->addError(ApplicationError::get('PARAMETER_INVALID',
        array('invalidParameters' => $invalidParameters)));
      return false;
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute() {

    $request = $this->getRequest();
    $response = $this->getResponse();
    $permissionManager = ObjectFactory::getInstance('permissionManager');
    $principalFactory = ObjectFactory::getInstance('principalFactory');

    // process actions
    if ($request->getAction() == 'checkPermissions') {
      $result = array();
      $permissions = $request->hasValue('operations') ? $request->getValue('operations') : array();
      foreach($permissions as $permission) {
        $keyParts = ActionKey::parseKey($permission);
        $result[$permission] = $permissionManager->authorize($keyParts['resource'], $keyParts['context'], $keyParts['action']);
      }
      $response->setValue('result', $result);
    }
    elseif ($request->getAction() == 'checkPermissionsOfUser') {
      $result = array();
      $permissions = $request->hasValue('operations') ? $request->getValue('operations') : array();
      $user = $request->hasValue('user') ? $principalFactory->getUser($request->getValue('user')) : null;
      foreach($permissions as $permission) {
        $keyParts = ActionKey::parseKey($permission);
        $result[$permission] = $permissionManager->authorize($keyParts['resource'], $keyParts['context'], $keyParts['action'],
                $user);
      }
      $response->setValue('result', $result);
    }
    elseif ($request->getAction() == 'createPermission') {
      $resource = $request->getValue('resource');
      $context = $request->getValue('context');
      $action = $request->getValue('action');
      $role = $request->getValue('role');
      $modifier = $request->getValue('modifier');

      $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
      $transaction->begin();
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $permissionManager->createPermission($resource, $context, $action, $role, $modifier);
      $transaction->commit();
    }
    elseif ($request->getAction() == 'removePermission') {
      $resource = $request->getValue('resource');
      $context = $request->getValue('context');
      $action = $request->getValue('action');
      $role = $request->getValue('role');

      $transaction = ObjectFactory::getInstance('persistenceFacade')->getTransaction();
      $transaction->begin();
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $permissionManager->removePermission($resource, $context, $action, $role);
      $transaction->commit();
    }
    $response->setAction('ok');
  }
}
?>