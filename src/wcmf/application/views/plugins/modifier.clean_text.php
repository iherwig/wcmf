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
 * $Id: modifier.utf8encode.php 929 2009-02-22 23:20:49Z iherwig $
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
function smarty_modifier_clean_text($text)
{
  $text = strip_tags($text);
  $text = preg_replace('/&nbsp;/', '', $text);
  return trim($text);
}
?>

