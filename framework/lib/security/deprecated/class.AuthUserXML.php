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
require_once(BASE."wcmf/lib/security/class.AuthUser.php");
require_once(BASE."wcmf/lib/security/class.UserManager.php");
require_once(BASE."wcmf/lib/util/class.XMLUtil.php");

/**
 * @class AuthUserXML
 * @ingroup Security
 * @brief AuthUser that gets configuration from an XML file
 * @deprecated Use AuthUser and UserXML instead
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthUserXML extends AuthUser
{
  /**
   * @see AuthUser::getUserData()
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
  function getUserData($login, $password)
  {
    $old_error_handler = set_error_handler("XMLUtilErrorHandler"); 
    $userData = array();
    
    // setup xml database
    $xml = new XMLUtil();

    // Security settings
    $aDbPermissions = array(
      			'GetNodeData'  => XMLDB_PERMISSION_ENABLE,
      			'GetChildData' => XMLDB_PERMISSION_ENABLE
    );
    // Pass down the security mode settings.
    $xml->bSecureMode = TRUE;
    foreach ($aDbPermissions as $MethodName => $Permission) 
    	$xml->aPermissions[$MethodName] = $Permission;

    // query database
    if (!$xml->Open($this->_initParams['filename'], FALSE, FALSE)) 
      WCMFException::throwEx("Could not open XML input: ".$this->_initParams['filename'], __FILE__, __LINE__);
    
    // get user node path
    $userQuery = "*/users/user[child::login[.='".$login."']][child::password[.='".$password."']]";
    $userPathArray = $xml->XmlDb->evaluate($userQuery);
    $userPath = $userPathArray[0];
    
    if ($userPath != '')
    {
      // get user values
      $userData['id'] = $xml->XmlDb->getAttributes($userPath, 'id');
      $userData['name'] = $xml->XmlDb->getData($userPath."/name");
      $userData['firstname'] = $xml->XmlDb->getData($userPath."/firstname");
      $userData['config'] = $xml->XmlDb->getData($userPath."/config");

      // get roles
      $userData['roles'] = array();
      $userRolesPathArray = $xml->XmlDb->evaluate($userPath.'/userroles/roleid');
      foreach ($userRolesPathArray as $userRolePath)
      {
        $roleId = $xml->XmlDb->getData($userRolePath);
        $rolePathArray = $xml->XmlDb->evaluate('*/roles/role[@id="'.$roleId.'"]/name');
	      array_push($userData['roles'], $xml->XmlDb->getData($rolePathArray[0]));
	  }
    }
    $xml->Close();
    set_error_handler($old_error_handler);
  	return $userData;
  }
}
?>
