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
namespace wcmf\lib\security\principal\impl;

use wcmf\lib\core\ObjectFactory;
use wcmf\lib\model\Node;
use wcmf\lib\persistence\BuildDepth;
use wcmf\lib\persistence\Criteria;
use wcmf\lib\security\principal\User;

/**
 * Default implementation of a user.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class AbstractUser extends Node implements User {

  private $_cachedRoles = array();
  private $_hasOwnRolesLoaded = false;

  /**
   * @see User::getUserId()
   */
  public function getUserId() {
    return $this->getOID()->getFirstId();
  }

  /**
   * Assign a role to the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function addRole($rolename) {
    if ($this->hasRole($rolename)) {
      return;
    }
    // add the role if existing
    $role = $this->getRoleByName($rolename);
    if ($role != null) {
      $this->addNode($role);
    }
  }

  /**
   * Remove a role from the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function removeRole($rolename) {
    if (!$this->hasRole($rolename)) {
      return;
    }
    // remove the role if existing
    $role = $this->getRoleByName($rolename);
    if ($role != null) {
      $this->deleteNode($role);
    }
  }

  /**
   * Check for a certain role in the user roles.
   * @param rolename The role name to check for. e.g. "administrators"
   * @return True/False whether the user has the role
   */
  public function hasRole($rolename) {
    $roles = $this->getRoles();
    for ($i=0; $i<sizeof($roles); $i++) {
      if ($roles[$i]->getName() == $rolename) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get the roles of a user.
   * @return An array holding the role names
   */
  public function getRoles() {
    $roleType = ObjectFactory::getInstance('userManager')->getRoleType();
    if (!$this->_hasOwnRolesLoaded) {
      // make sure that the roles are loaded

      // allow this in any case (prevent infinite loops when trying to authorize)
      $permissionManager = ObjectFactory::getInstance('permissionManager');
      $isAnonymous = $permissionManager->isAnonymous();
      if (!$isAnonymous) {
        $permissionManager->deactivate();
      }
      $mapper = $this->getMapper();
      foreach ($mapper->getRelations() as $relation) {
        if ($relation->getOtherType() == $roleType) {
          $this->loadChildren($relation->getOtherRole());
        }
      }
      // reactivate the PermissionManager if necessary
      if (!$isAnonymous) {
        $permissionManager->activate();
      }
      $this->_hasOwnRolesLoaded = true;
    }
    return $this->getChildrenEx(null, null, $roleType, null);
  }

  /**
   * Get a Role instance whose name is given
   * @param rolename The name of the role
   * @return A reference to the role or null if nor existing
   */
  protected function getRoleByName($rolename) {
    if (!isset($this->_cachedRoles[$rolename])) {
      // load the role
      $roleType = ObjectFactory::getInstance('userManager')->getRoleType();
      $persistenceFacade = ObjectFactory::getInstance('persistenceFacade');
      $role = $persistenceFacade->loadFirstObject($roleType, BuildDepth::SINGLE,
                  array(
                      new Criteria($roleType, 'name', '=', $rolename)
                  ), null);
      if ($role != null) {
        $this->_cachedRoles[$rolename] = $role;
      }
      else {
        return null;
      }
    }
    return $this->_cachedRoles[$rolename];
  }

  /**
   * This class caches loaded roles for performance reasons. After retrieving
   * an instance from the session, the cache is invalid and must be reseted using
   * this method.
   */
  public function resetRoleCache() {
    $this->_cachedRoles = array();
    $this->_hasOwnRolesLoaded = false;
  }
}
?>
