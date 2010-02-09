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
require_once(BASE."wcmf/lib/security/class.UserImpl.php");
 
/**
 * @class User
 * @ingroup Security
 * @brief Implementation of a XML system user.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserXML extends UserImpl
{
  /**
   * Default constructor.
   */
  function UserXML($oid=null, $type='UserXML')
  {
    parent::UserImpl($oid, $type);
  }

  /**
   * @see User::getUserOID()
   */  
  function &getUser($login, $password)
  {
    $objectFactory = &ObjectFactory::getInstance();
    $userManager = &$objectFactory->createInstanceFromConfig('implementation', 'UserManager');
    $user = $userManager->getUser($login);
    if ($user != null && $user->getPassword() == $password)
      return $user;
    return null;
  }

  /**
   * @see User::getRoleByName()
   */
  function &getRoleByName($rolename)
  {
    // load the role
    $objectFactory = &ObjectFactory::getInstance();
    $userManager = &$objectFactory->createInstanceFromConfig('implementation', 'UserManager');
    $role = $userManager->getRole($rolename);
    if ($role != null)
      return $role;
    else
      return null;
  }
}
?>
