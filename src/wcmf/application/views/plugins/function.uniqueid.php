<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/**
 * Output an unique id.
 *
 * Example:
 * @code
 * {uniqueid}
 * @endcode
 *
 * @param $params Not used
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_function_uniqueid($params, Smarty_Internal_Template $template) {
  $uid = md5(uniqid(ip2long($_SERVER['REMOTE_ADDR']) ^ (int)$_SERVER['REMOTE_PORT'] ^ getmypid() ^ @disk_free_space('/tmp'), 1));
  echo $uid;
}
?>