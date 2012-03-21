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
namespace wcmf\lib\presentation\smarty_plugins;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.uniqueid.php
* Type:     function
* Name:     uniqueid
* Purpose:  output an unique id or assign it to a smarty variable
* Usage:    e.g. {uniqueid} or {uniqueid varname="uid"}
* -------------------------------------------------------------
*/
function smarty_function_uniqueid($params, &$smarty)
{
  $uid = md5(uniqid(ip2long($_SERVER['REMOTE_ADDR']) ^ (int)$_SERVER['REMOTE_PORT'] ^ @getmypid() ^ @disk_free_space('/tmp'), 1));
  if (isset($params['varname'])) {
    $smarty->assign($params['varname'], $uid);
  }
  else {
  	echo $uid;
  }
}
?>