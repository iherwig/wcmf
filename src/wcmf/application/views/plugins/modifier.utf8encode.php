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
* File:     modifier.utf8encode.php
* Type:     function
* Name:     utf8encode
* Purpose:  encode a string to utf8
* Usage:    e.g. {$stext|utf8encode}
* -------------------------------------------------------------
*/
function smarty_modifier_utf8encode($text) {
  return htmlspecialchars(utf8_encode($text));
}
?>

