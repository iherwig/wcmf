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
function smarty_function_uniqueid($params, \Smarty_Internal_Template $template) {
  $uid = md5(uniqid(ip2long($_SERVER['REMOTE_ADDR']) ^ (int)$_SERVER['REMOTE_PORT'] ^ @getmypid() ^ @disk_free_space('/tmp'), 1));
  if (isset($params['varname'])) {
    $template->assign($params['varname'], $uid);
  }
  else {
    echo $uid;
  }
}
?>