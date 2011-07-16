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
require_once(WCMF_BASE."wcmf/lib/model/Node.php");
require_once(WCMF_BASE."wcmf/lib/persistence/PersistenceFacade.php");
require_once(WCMF_BASE."wcmf/lib/model/NodeIterator.php");
require_once(WCMF_BASE."wcmf/lib/visitor/CommitVisitor.php");

/**
 * @class User
 * @ingroup Security
 * @brief Abstract base class for user classes that represent a system user.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
abstract class User extends Node
{
  private $_cachedRoles = array();
  private $_hasOwnRolesLoaded = false;

  /**
   * Constructor
   * @param oid ObjectId instance (optional)
   */
  function __construct($oid=null)
  {
    if ($oid == null) {
      $oid = new ObjectId('User');
    }
    parent::__construct($oid);
  }
  /**
   * Get the user instance by login and password.
   * The default implementation searches the user using the PersistenceFacade.
   * This method may be called in a static way.
   * Subclasses will override this to implement special retrieval.
   * @return A reference to the instance or null if not found.
   */
  public function getUser($login, $password)
  {
    $persistenceFacade = PersistenceFacade::getInstance();
    $userType = UserManager::getUserClassName();
    $user = $persistenceFacade->loadFirstObject($userType, BUILDDEPTH_SINGLE,
                  array(
                      new Criteria($userType, 'login', '=', $login),
                      new Criteria($userType, 'password', '=', $password)
                  ), null);
    if ($user != null)
    {
      // initially load roles
      $user->getRoles();
      return $user;
    }
    return null;
  }

  /**
   * Get the id of the user.
   * @return The id.
   */
  public function getUserId()
  {
    return $this->getOID()->getFirstId();
  }

  /**
   * Set the login of the user.
   * @param login The login of the user.
   */
  abstract function setLogin($login);

  /**
   * Get the login of the user.
   * @return The login of the user.
   */
  abstract function getLogin();

  /**
   * Set the password of the user.
   * @param password The unencrypted password of the user.
   */
  abstract function setPassword($password);

  /**
   * Get the password of the user.
   * @return The unencrypted password of the user.
   */
  abstract function getPassword();

  /**
   * Set the name of the user.
   * @param name The name of the user.
   */
  abstract function setName($name);

  /**
   * Get name of the user.
   * @return The name of the user.
   */
  abstract function getName();

  /**
   * Set the firstname of the user.
   * @param firstname The firstname of the user.
   */
  abstract function setFirstname($firstname);

  /**
   * Get the firstname of the user.
   * @return The firstname of the user.
   */
  abstract function getFirstname();

  /**
   * Set the configuration file of the user.
   * @param config The configuration file of the user.
   */
  abstract function setConfig($config);

  /**
   * Get the configuration file of the user.
   * @return The configuration file of the user.
   */
  abstract function getConfig();

  /**
   * Assign a role to the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function addRole($rolename)
  {
    if ($this->hasRole($rolename)) {
      return;
    }
    // add the role if existing
    $role = $this->getRoleByName($rolename);
    if ($role != null) {
      $this->addNode($role);
      // commit the changes
      $this->save();
    }
  }

  /**
   * Remove a role from the user.
   * @param rolename The role name. e.g. "administrators"
   */
  public function removeRole($rolename)
  {
    if (!$this->hasRole($rolename)) {
      return;
    }
    // remove the role if existing
    $role = $this->getRoleByName($rolename);
    if ($role != null)
    {
      $this->deleteNode($role);
      // commit the changes
      $this->save();
    }
  }

  /**
   * Check for a certain role in the user roles.
   * @param rolename The role name to check for. e.g. "administrators"
   * @return True/False whether the user has the role
   */
  public function hasRole($rolename)
  {
    $roles = $this->getRoles();
    for ($i=0; $i<sizeof($roles); $i++)
    {
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
  public function getRoles()
  {
    if (!$this->_hasOwnRolesLoaded)
    {
      // make sure that the roles are loaded

      // allow this in any case (prevent infinite loops when trying to authorize)
      $rightsManager = RightsManager::getInstance();
      $isAnonymous = $rightsManager->isAnonymous();
      if (!$isAnonymous) {
        $rightsManager->deactivate();
      }
      $this->loadChildren(UserManager::getRoleClassName());

      // reactivate the RightsManager if necessary
      if (!$isAnonymous) {
        $rightsManager->activate();
      }
      $this->_hasOwnRolesLoaded = true;
    }
    return $this->getChildrenEx(null, UserManager::getRoleClassName(), null, null);
  }

  /**
   * Get a Role instance whose name is given
   * @param rolename The name of the role
   * @return A reference to the role or null if nor existing
   */
  protected function getRoleByName($rolename)
  {
    if (!isset($this->_cachedRoles[$rolename]))
    {
      // load the role
      $persistenceFacade = PersistenceFacade::getInstance();
      $role = $persistenceFacade->loadFirstObject(UserManager::getRoleClassName(), BUILDDEPTH_SINGLE, array('name' => $rolename));
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
  public function resetRoleCache()
  {
    $this->_cachedRoles = array();
    $this->_hasOwnRolesLoaded = false;
  }
}
?>
