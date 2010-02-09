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
require_once(BASE."wcmf/lib/security/class.UserManager.php");
require_once(BASE."wcmf/lib/security/class.User.php");
require_once(BASE."wcmf/lib/security/class.Role.php");
require_once(BASE."wcmf/lib/util/class.XMLUtil.php");

/**
 * @class UserManagerXML
 * @ingroup Security
 * @brief UserManagerXML is a UserManager that stores user and role information in an XML file.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserManagerXML extends UserManager
{
  var $_xml = null; // XML database
  var $_old_error_handler = null;

  var $_initParams = null;
  var $_fileOpened = false;
  
  /**
   * Open the XML database.
   * @param lock True/False wether a lock is required or not
   */
  function openConnection($lock=true)
  {
    $this->_old_error_handler = set_error_handler("XMLUtilErrorHandler");
    
    // setup xml database
    $this->_xml = new XMLUtil();
    $this->_xml->SetOptions(array('TimeStampFlag' => 0, 'XmlOptions' => array(XML_OPTION_SKIP_WHITE => 1)));

    // Security settings
    $aDbPermissions = array(
    );
    // Pass down the security mode settings.
    $this->_xml->bSecureMode = TRUE;
    foreach ($aDbPermissions as $MethodName => $Permission) 
    	$this->_xml->aPermissions[$MethodName] = $Permission;

    if (!$this->_xml->Open($this->_initParams['filename'], FALSE, $lock)) 
      WCMFException::throwEx("Could not open XML input: ".$this->_initParams['filename'], __FILE__, __LINE__);
    $this->_xml->XmlDb->setVerbose(0);
    
    $this->_fileOpened = true;

    // call commitTransaction() on shutdown for automatic transaction end
    register_shutdown_function(array(&$this, 'closeConnection'));
  }

  /**
   * Close the XML database.
   */
  function closeConnection()
  {
    if ($this->_old_error_handler != null)
      set_error_handler($this->_old_error_handler);
    $this->_xml->Close();
    $this->_fileOpened = false;
  }

  /**
   * Set Modification flag if no error occured.
   */
  function validateChanges()
  {
    if (($err = $this->_xml->XmlDb->getLastError()) != '')
    {
      // on error discard all changes
      $this->_xml->bModifyFlag = false;
      WCMFException::throwEx($err);
    }
    else
      $this->_xml->bModifyFlag = true;
  }

  /**
   * @see UserManager::startTransaction()
   */
  function startTransaction() 
  {
    if (!$this->_fileOpened)
      $this->openConnection();
  }

  /**
   * @see UserManager::commitTransaction()
   */
  function commitTransaction() 
  {
    if ($this->_fileOpened)
      $this->closeConnection();
  }

  /**
   * @see UserManager::rollbackTransaction()
   */
  function rollbackTransaction() 
  {
    if ($this->_fileOpened)
    {
      $this->_xml->bModifyFlag = FALSE;
      $this->closeConnection();
    }
  }

  /**
   * @see UserManager::initialize()
   *
   * @note This class relies on the following dtd
   * @verbatim
    <!ELEMENT XmlDatabase (#PCDATA | nextID | users | roles)*>
    
    <!ELEMENT nextID (#PCDATA)>
    
    <!ELEMENT users (#PCDATA | user)*>
    <!ELEMENT user (#PCDATA | name | firstname | login | password | config | userroles)*>
    <!ATTLIST user id CDATA #IMPLIED>
    <!ELEMENT firstname (#PCDATA)>
    <!ELEMENT login (#PCDATA)>
    <!ELEMENT password (#PCDATA)>
    <!ELEMENT config (#PCDATA)>
    <!ELEMENT userroles (#PCDATA | roleid)*>
    <!ELEMENT roleid (#PCDATA)>
    
    <!ELEMENT roles (#PCDATA | role)*>
    <!ELEMENT role (#PCDATA | name)*>
    <!ATTLIST role id CDATA #IMPLIED>
    
    <!ELEMENT name (#PCDATA)>
     @endverbatim
   *
   * @note Initialization data given in the constructor require the following keys: 
   *       filename
   */
  function initialize($params)
  {
    $userRepository = array();
    $userRepository['users'] = array();
    $userRepository['roles'] = array();
    
    // connect to database
    $this->_initParams = $params;
    $this->openConnection(false);

    // query database
    // users
    $userQuery = "*/users/*";
    $userPathArray = $this->_xml->XmlDb->evaluate($userQuery);
    foreach ($userPathArray as $userPath)
    {
      $curUserID = $this->_xml->XmlDb->getAttributes($userPath, 'id');
      $user = &$this->createUserInstance();
      $user->setDBID($this->_xml->XmlDb->getAttributes($userPath, 'id'));
	    $user->setLogin($this->_xml->XmlDb->getData($userPath."/login"));
	    $user->setPassword($this->_xml->XmlDb->getData($userPath."/password"));
      $user->setName($this->_xml->XmlDb->getData($userPath."/name"));
      $user->setFirstname($this->_xml->XmlDb->getData($userPath."/firstname"));
      $user->setConfig($this->_xml->XmlDb->getData($userPath."/config"));
      $userRolesPathArray = $this->_xml->XmlDb->evaluate($userPath.'/userroles/roleid');
      foreach ($userRolesPathArray as $userRolePath)
      {
        $roleId = $this->_xml->XmlDb->getData($userRolePath);
        $rolePathArray = $this->_xml->XmlDb->evaluate('*/roles/role[@id="'.$roleId.'"]/name');
        $role = &$this->createRoleInstance();
        $role->setDBID($roleId);
        $role->setName($this->_xml->XmlDb->getData($rolePathArray[0]));
	      $user->addChild($role);
	    }
      $userRepository['users'][sizeof($userRepository['users'])] = &$user;
  	}
    // roles
    $roleQuery = "*/roles/*";
    $rolePathArray = $this->_xml->XmlDb->evaluate($roleQuery);
    foreach ($rolePathArray as $rolePath)
    {
      $curRoleID = $this->_xml->XmlDb->getAttributes($rolePath, 'id');
      $role = &$this->createRoleInstance();
      $role->setDBID($curRoleID);
      $role->setName($this->_xml->XmlDb->getData($rolePath."/name"));
	    $userRepository['roles'][sizeof($userRepository['roles'])] = $role;
  	}

  	return $userRepository;
  }
  
  /**
   * Create a user instance
   */
  function &createUserInstance()
  {
    $className = UserManager::getUserClassName();
    return new $className;
  }
  
  /**
   * Create a role instance
   */
  function &createRoleInstance()
  {
    $className = UserManager::getRoleClassName();
    return new $className;
  }
  
  /**
   * @see UserManager::createUserImpl()
   */
  function &createUserImpl($name, $firstname, $login, $password)
  {
    $newID = $this->_xml->GetNextInsertId();

    // find parent
    $parentPathArray = $this->_xml->XmlDb->evaluate('*/users');

    // add user nodes
    $userPath = $this->_xml->XmlDb->appendChild($parentPathArray[0], '<user/>');
    $this->_xml->XmlDb->setAttributes($userPath, array('id' => $newID));

    $namePath = $this->_xml->XmlDb->appendChild($userPath, '<name/>');
    $this->_xml->XmlDb->insertData($namePath, $name);

    $firstnamePath = $this->_xml->XmlDb->appendChild($userPath, '<firstname/>');
    $this->_xml->XmlDb->insertData($firstnamePath, $firstname);

    $loginPath = $this->_xml->XmlDb->appendChild($userPath, '<login/>');
    $this->_xml->XmlDb->insertData($loginPath, $login);

    $passwordPath = $this->_xml->XmlDb->appendChild($userPath, '<password/>');
    $this->_xml->XmlDb->insertData($passwordPath, $password);
    
    $configPath = $this->_xml->XmlDb->appendChild($userPath, '<config/>');
    
    $rolesPath = $this->_xml->XmlDb->appendChild($userPath, '<userroles/>');

    // save if no error
    $this->validateChanges();
    
    $className = UserManager::getUserClassName();
    $user = new $className;
    $user->setDBID($newID);
    $user->setLogin($login);
    $user->setPassword($password);
    $user->setName($name);
    $user->setFirstname($firstname);
    return $user;
  }

  /**
   * @see UserManager::removeUserImpl()
   */  
  function removeUserImpl(&$user)
  {
    // find user
    $userQuery = '*/users/user[@id="'.$user->getDBID().'"]';
    $userPathArray = $this->_xml->XmlDb->evaluate($userQuery);

    // remove user
    $this->_xml->_RemoveRecord($userPathArray);
    
    // save if no error
    $this->validateChanges();
  }

  /**
   * @see UserManager::setUserPropertyImpl()
   */  
  function setUserPropertyImpl(&$user, $property, $value)
  {
    // find property
    $propertyQuery = '*/users/user[@id="'.$user->getDBID().'"]/'.$property;
    $propertyPathArray = $this->_xml->XmlDb->evaluate($propertyQuery);
    
    $this->_xml->XmlDb->replaceData($propertyPathArray[0], $value);

    // save if no error
    $this->validateChanges();
  }

  /**
   * @see UserManager::createRoleImpl()
   */  
  function &createRoleImpl($name)
  {
    $newID = $this->_xml->GetNextInsertId();

    // find parent
    $parentPathArray = $this->_xml->XmlDb->evaluate('*/roles');

    // add role node
    $rolePath = $this->_xml->XmlDb->appendChild($parentPathArray[0], '<role/>');
    $this->_xml->XmlDb->setAttributes($rolePath, array('id' => $newID));
    $namePath = $this->_xml->XmlDb->appendChild($rolePath, '<name/>');
    $this->_xml->XmlDb->insertData($namePath, $name);

    // save if no error
    $this->validateChanges();
    
    return new Role($newID, $name);
  }

  /**
   * @see UserManager::removeRoleImpl()
   */  
  function removeRoleImpl(&$role)
  {
    // find user roles
    $rolesQuery = '*/users/user/userroles/roleid[.="'.$role->getDBID().'"]';
    $rolesPathArray = $this->_xml->XmlDb->evaluate($rolesQuery);

    // remove role from users
    $this->_xml->_RemoveRecord($rolesPathArray);
    
    // find role
    $roleQuery = '*/roles/role[@id="'.$role->getDBID().'"]';
    $rolePathArray = $this->_xml->XmlDb->evaluate($roleQuery);

    // remove role
    $this->_xml->_RemoveRecord($rolePathArray);
    
    // save if no error
    $this->validateChanges();
  }

  /**
   * @see UserManager::setRolePropertyImpl()
   */  
  function setRolePropertyImpl(&$role, $property, $value)
  {
    // find property
    $propertyQuery = '*/roles/role[@id="'.$role->getDBID().'"]/'.$property;
    $propertyPathArray = $this->_xml->XmlDb->evaluate($propertyQuery);
    
    $this->_xml->XmlDb->replaceData($propertyPathArray[0], $value);

    // save if no error
    $this->validateChanges();
  }

  /**
   * @see UserManager::addUserToRoleImpl()
   */  
  function addUserToRoleImpl(&$role, &$user)
  {
    // find user roles
    $rolesQuery = '*/users/user[@id="'.$user->getDBID().'"]/userroles';
    $rolesPathArray = $this->_xml->XmlDb->evaluate($rolesQuery);

    // add role
    $rolePath = $this->_xml->XmlDb->appendChild($rolesPathArray[0], '<roleid/>');
    $this->_xml->XmlDb->insertData($rolePath, $role->getDBID());
    
    // save if no error
    $this->validateChanges();
  }

  /**
   * @see UserManager::removeUserFromRoleImpl()
   */  
  function removeUserFromRoleImpl(&$role, &$user)
  {
    // find user roles
    $rolesQuery = '*/users/user[@id="'.$user->getDBID().'"]/userroles/roleid[.="'.$role->getDBID().'"]';
    $rolesPathArray = $this->_xml->XmlDb->evaluate($rolesQuery);

    // remove role
    $this->_xml->_RemoveRecord($rolesPathArray);
    
    // save if no error
    $this->validateChanges();
  }
}
?>
