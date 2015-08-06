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
* File:     modifier.strip_p_tags.php
* Type:     function
* Name:     strip_p_tags
* Purpose:  remove enclosing p tags from a string
* Usage:    e.g. {$text|strip_p_tags}
* -------------------------------------------------------------
*/
function smarty_modifier_strip_p_tags($text) {
  return preg_replace('/^\s*<p>|<\/p>\s*$/', '', $text);
}
?>