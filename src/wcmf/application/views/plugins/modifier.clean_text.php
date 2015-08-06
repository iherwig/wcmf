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
* File:     modifier.clean_text.php
* Type:     function
* Name:     clean_text
* Purpose:  remove all html tags, &nbsp; and outer whitespace
* Usage:    e.g. {$text|clean_text}
* -------------------------------------------------------------
*/
function smarty_modifier_clean_text($text) {
  $text = strip_tags($text);
  $text = preg_replace('/&nbsp;/', '', $text);
  return trim($text);
}
?>