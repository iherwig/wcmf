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
require_once(BASE."wcmf/lib/util/class.InifileParser.php");
require_once(BASE."wcmf/3rdparty/adodb/adodb.inc.php");

/**
 * @class AuthUserRDB
 * @ingroup Security
 * @brief AuthUser that gets configuration from an database
 * @deprecated Use AuthUser and UserRDB instead
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class AuthUserRDB extends AuthUser
{
  /**
   * @see AuthUser::getUserData()
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
   *       if dbPrefix is given it will be appended to every table string, which is
   *       usefull if different cms operate on the same database
   */
  function getUserData($login, $password)
  {
    $userData = array();
    
    // connect to database
	  $conn = &ADONewConnection($this->_initParams['dbType']);
    $connected = $conn->PConnect($this->_initParams['dbHostName'],$this->_initParams['dbUserName'],$this->_initParams['dbPassword'],$this->_initParams['dbName']);
    if (!$connected)
      WCMFException::throwEx($conn->ErrorMsg(), __FILE__, __LINE__);

    $conn->replaceQuote = "\'";
    $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
    define(ADODB_OUTP, "gError");

    // get database prefix if defined
    $this->_dbPrefix = $params['dbPrefix'];

    // log sql if requested
    $parser = &InifileParser::getInstance();
    if (($logSQL = $parser->getValue('logSQL', 'cms')) === false)
      $logSQL = 0;
    $conn->LogSQL($logSQL);

    // query database
    $sqlStr = "SELECT ".$dbPrefix."user.id, ".$dbPrefix."user.name, ".$dbPrefix."user.firstname, ".$dbPrefix."user.config, ".$dbPrefix."role.name as rolename
               FROM ".$dbPrefix."user LEFT JOIN ".$dbPrefix."nm_user_role ON ".$dbPrefix."user.id=".$dbPrefix."nm_user_role.fk_user_id LEFT JOIN ".$dbPrefix."role 
               ON ".$dbPrefix."nm_user_role.fk_role_id=".$dbPrefix."role.id
               WHERE ".$dbPrefix."user.login='".$login."' AND ".$dbPrefix."user.password='".$password."';";
    $rs = &$conn->Execute($sqlStr);
    $firstDone = false;
	  while ($rs && $row = $rs->FetchRow())
	  {
  	  if (!$firstDone)
  	  {
  	    $userData['id'] = $row['id'];
  	    $userData['name'] = $row['name'];
  	    $userData['firstname'] = $row['firstname'];
  	    $userData['config'] = $row['config'];
  	    $userData['roles'] = array();
  	    $firstDone = true;
  	  }
  	  array_push($userData['roles'], $row['rolename']);
  	}
  	$conn->Close();

  	return $userData;
  }
}
?>
