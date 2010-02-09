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
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");

/**
 * @class UserManagerRDB
 * @ingroup Security
 * @brief UserManagerRDB is a UserManager that stores user and role information in a database.
 * @deprecated Use UserManagerRDB instead
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class UserManagerRDB extends UserManager
{
  var $_conn = null;   // database connection
  var $_dbPrefix = ''; // database prefix (if given in the configuration file)

  /**
   * Open the database connection.
   * @param params Assoziative array with the following keys: dbType, dbHostName, dbUserName, dbPassword, dbName
   *               if dbPrefix is given it will be appended to every table string, which is
   *               usefull if different cms operate on the same database
   */
  function openConnection($params)
  {
    // create new connection
    $this->_conn = &ADONewConnection($params['dbType']);
    $connected = $this->_conn->PConnect($params['dbHostName'],$params['dbUserName'],$params['dbPassword'],$params['dbName']);
    if (!$connected)
      WCMFException::throwEx($this->_conn->ErrorMsg(), __FILE__, __LINE__);
    
    $this->_conn->replaceQuote = "\'";
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    define(ADODB_OUTP, "gError");

    // get database prefix if defined
    $this->_dbPrefix = $params['dbPrefix'];

    // log sql if requested
    $parser = &InifileParser::getInstance();
    if (($logSQL = $parser->getValue('logSQL', 'cms')) === false)
      $logSQL = 0;
    $this->_conn->LogSQL($logSQL);
  }

  /**
   * @see UserManager::initialize()
   *
   * @note This class relies on the following database tables
   * @verbatim
     CREATE TABLE nm_user_role (
       fk_user_id int(11) NOT NULL default '0',
       fk_role_id int(11) NOT NULL default '0',
       KEY fk_user_id (fk_user_id,fk_role_id)
     ) TYPE=MyISAM;
     
     CREATE TABLE role (
       id int(11) NOT NULL auto_increment,
       name varchar(50) default NULL,
       PRIMARY KEY  (id)
     ) TYPE=MyISAM;
     
     CREATE TABLE user (
       id int(11) NOT NULL auto_increment,
       name varchar(50) default NULL,
       firstname varchar(50) default NULL,
       login varchar(50) default NULL,
       password varchar(50) default NULL,
       config varchar(255) default NULL,
       PRIMARY KEY  (id)
     ) TYPE=MyISAM;
     @endverbatim
   *
   * @note Initialization data given in the constructor require the following keys: 
   *       dbType, dbHostName, dbUserName, dbPassword, dbName
   */
  function initialize($params)
  {
    $userRepository = array();
    $userRepository['users'] = array();
    $userRepository['roles'] = array();
    
    // connect to database
    $this->openConnection($params);

    // query database
    // users
    $sqlStr = "SELECT ".$this->_dbPrefix."user.id, ".$this->_dbPrefix."user.name, ".$this->_dbPrefix."user.login, ".$this->_dbPrefix."user.password, ".$this->_dbPrefix."user.firstname, ".$this->_dbPrefix."user.config, ".$this->_dbPrefix."role.name AS rolename
               FROM ".$this->_dbPrefix."user LEFT JOIN ".$this->_dbPrefix."nm_user_role ON ".$this->_dbPrefix."user.id=".$this->_dbPrefix."nm_user_role.fk_user_id LEFT JOIN ".$this->_dbPrefix."role 
               ON ".$this->_dbPrefix."nm_user_role.fk_role_id=".$this->_dbPrefix."role.id ORDER BY user.id;";
    $rs = &$this->_conn->Execute($sqlStr);
    $curUserID = '';
	  while ($rs && $row = $rs->FetchRow())
	  {
	    if ($row['id'] != $curUserID)
	    {
	      $curUserID = $row['id'];
        $user = new User($curUserID, $row['login'], $row['password'], $row['name'], $row['firstname'], $row['config'], array());
  	    $userRepository['users'][$curUserID] = $user;
  	  }
  	  if ($row['rolename'] != '')
	      $userRepository['users'][$curUserID]->addRole($row['rolename']);
  	}
    // roles
    $sqlStr = "SELECT ".$this->_dbPrefix."role.id, ".$this->_dbPrefix."role.name FROM ".$this->_dbPrefix."role;";
    $rs = &$this->_conn->Execute($sqlStr);
	  while ($rs && $row = $rs->FetchRow())
	    $userRepository['roles'][$row['id']] = new Role($row['id'], $row['name']);

  	return $userRepository;
  }

  /**
   * @see UserManager::createUserImpl()
   */
  function createUserImpl($name, $firstname, $login, $password)
  {
    $newID = $this->_conn->GenID();
    $sqlStr = "INSERT INTO ".$this->_dbPrefix."user (id, name, firstname, login, password) VALUES (".$this->_conn->qstr($newID).", ".
              $this->_conn->qstr($name).", ".$this->_conn->qstr($firstname).", ".$this->_conn->qstr($login).", ".$this->_conn->qstr($password).");";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error creating user '".$login."'. See log file for details.", __FILE__, __LINE__);
    }

    return $newID;
  }

  /**
   * @see UserManager::removeUserImpl()
   */  
  function removeUserImpl($user)
  {
    // remove user from all rows
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."nm_user_role WHERE fk_user_id=".$this->_conn->qstr($user->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error removing user '".$user->getLogin()."' from his roles. See log file for details.", __FILE__, __LINE__);
    }
    // remove user
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."user WHERE id=".$this->_conn->qstr($user->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error removing user '".$user->getLogin()."'. See log file for details.", __FILE__, __LINE__);
    }
  }

  /**
   * @see UserManager::setUserPropertyImpl()
   */  
  function setUserPropertyImpl($user, $property, $value)
  {
    $sqlStr = "UPDATE ".$this->_dbPrefix."user SET ".$property."=".$this->_conn->qstr($value)." WHERE id=".$this->_conn->qstr($user->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error changing property '".$property."' for user '".$user->getLogin()."'. See log file for details.", __FILE__, __LINE__);
    }
  }

  /**
   * @see UserManager::createRoleImpl()
   */  
  function createRoleImpl($name)
  {
    $newID = $this->_conn->GenID();
    $sqlStr = "INSERT INTO ".$this->_dbPrefix."role (id, name) VALUES (".$this->_conn->qstr($newID).", ".$this->_conn->qstr($name).");";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error creating role '".$name."'. See log file for details.", __FILE__, __LINE__);
    }

    return $newID;
  }

  /**
   * @see UserManager::removeRoleImpl()
   */  
  function removeRoleImpl($role)
  {
    // remove role from all users
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."nm_user_role WHERE fk_role_id=".$this->_conn->qstr($role->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error removing role '".$role->getName()."' from her users. See log file for details.", __FILE__, __LINE__);
    }
    // remove role
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."role WHERE id=".$this->_conn->qstr($role->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error removing role '".$role->getName()."'. See log file for details.", __FILE__, __LINE__);
    }
  }

  /**
   * @see UserManager::setRolePropertyImpl()
   */  
  function setRolePropertyImpl($role, $property, $value)
  {
    $sqlStr = "UPDATE ".$this->_dbPrefix."role SET ".$property."=".$this->_conn->qstr($value)." WHERE id=".$this->_conn->qstr($role->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error changing property '".$property."' for role '".$role->getName()."'. See log file for details.", __FILE__, __LINE__);
    }
  }

  /**
   * @see UserManager::addUserToRoleImpl()
   */  
  function addUserToRoleImpl($role, $user)
  {
    $sqlStr = "INSERT INTO ".$this->_dbPrefix."nm_user_role (fk_user_id, fk_role_id) VALUES (".$this->_conn->qstr($user->getID()).", ".$this->_conn->qstr($role->getID()).");";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error adding user '".$user->getLogin()."' to role '".$role->getName()."'. See log file for details.", __FILE__, __LINE__);
    }
  }

  /**
   * @see UserManager::removeUserFromRoleImpl()
   */  
  function removeUserFromRoleImpl($role, $user)
  {
    $sqlStr = "DELETE FROM ".$this->_dbPrefix."nm_user_role WHERE fk_user_id=".$this->_conn->qstr($user->getID())." AND fk_role_id=".$this->_conn->qstr($role->getID()).";";
    if ($this->_conn->Execute($sqlStr) === false)
    {
      Log::error($this->_conn->ErrorMsg().". Your query was: ".$sqlStr, __CLASS__);
      WCMFException::throwEx("Error removing user '".$user->getLogin()."' from role '".$role->getName()."'. See log file for details.", __FILE__, __LINE__);
    }
  }
}
?>
