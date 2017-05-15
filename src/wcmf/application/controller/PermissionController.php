<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\application\controller;

use wcmf\lib\config\ActionKey;
use wcmf\lib\presentation\ApplicationError;
use wcmf\lib\presentation\Controller;
use wcmf\lib\security\PermissionManager;

/**
 * PermissionController checks, gets and sets permissions.
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
 * <div> __Action__ getPermissions </div>
 * <div>
 * Get the permissions on an operation.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `operation`      | A resource/context/action triple in the form _resource?context?action_
 * | _out_ `result`        | Assoziative array with keys 'default' (boolean), 'allow', 'deny' (arrays of role names) or null, if no permissions are defined.
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ setPermissions </div>
 * <div>
 * Set the permissions on an operation.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `operation`      | A resource/context/action triple in the form _resource?context?action_
 * | _in_ `permissions`    | Assoziative array with keys 'default' (boolean), 'allow', 'deny' (arrays of role names).
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ createPermission </div>
 * <div>
 * Create/Change a permission for a role on an operation.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `operation`      | A resource/context/action triple in the form _resource?context?action_
 * | _in_ `role`           | The role to add.
 * | _in_ `modifier`       | _+_ or _-_ whether to allow or disallow the action for the role.
 * </div>
 * </div>
 *
 * <div class="controller-action">
 * <div> __Action__ removePermission </div>
 * <div>
 * Remove a role from a permission on an operation.
 * | Parameter             | Description
 * |-----------------------|-------------------------
 * | _in_ `operation`      | A resource/context/action triple in the form _resource?context?action_
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
  protected function validate() {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $invalidParameters = [];
    if ($request->getAction() == 'createPermission' || $request->getAction() == 'removePermission' ||
             $request->getAction() == 'getPermissions' || $request->getAction() == 'setPermissions') {
      foreach (['operation'] as $param) {
        if(!$request->hasValue($param)) {
          $invalidParameters[] = $param;
        }
      }
    }
    if ($request->getAction() == 'createPermission') {
      $permissions = $request->getValue('permissions');
      if (!isset($permissions['allow']) || !isset($permissions['deny'])) {
        $invalidParameters[] = 'permissions';
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
        ['invalidParameters' => $invalidParameters]));
      return false;
    }
    return true;
  }

  /**
   * @see Controller::doExecute()
   */
  protected function doExecute($method=null) {
    $request = $this->getRequest();
    $response = $this->getResponse();
    $permissionManager = $this->getPermissionManager();
    $action = $request->getAction();

    // process actions
    if (strpos($action, 'check') === 0) {
      $result = [];
      $operations = $request->hasValue('operations') ? $request->getValue('operations') : [];
      $user = $action == 'checkPermissionsOfUser' ? $request->getValue('user') : null;

      foreach($operations as $operation) {
        $opParts = ActionKey::parseKey($operation);
        $result[$operation] = $permissionManager->authorize($opParts['resource'], $opParts['context'], $opParts['action'],
                $user);
      }
      $response->setValue('result', $result);
    }
    else {
      $operation = $request->getValue('operation');
      $opParts = ActionKey::parseKey($operation);
      $opResource = $opParts['resource'];
      $opContext = $opParts['context'];
      $opAction = $opParts['action'];

      if ($action == 'getPermissions') {
        $result = $permissionManager->getPermissions($opResource, $opContext, $opAction);
        $response->setValue('result', $result);
      }
      elseif ($action == 'setPermissions') {
        $this->requireTransaction();
        $permissions = $request->getValue('permissions');
        $permissionManager->setPermissions($opResource, $opContext, $opAction, $permissions);
      }
      elseif ($action == 'createPermission') {
        $this->requireTransaction();
        $role = $request->getValue('role');
        $modifier = $request->getValue('modifier');
        $permissionManager->createPermission($opResource, $opContext, $opAction, $role, $modifier);
      }
      elseif ($action == 'removePermission') {
        $this->requireTransaction();
        $role = $request->getValue('role');
        $permissionManager->removePermission($opResource, $opContext, $opAction, $role);
      }
    }
    $response->setAction('ok');
  }
}
?>