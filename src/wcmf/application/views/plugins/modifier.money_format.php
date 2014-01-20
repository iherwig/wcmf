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

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     modifier.modifier_money_format.php
* Type:     function
* Name:     modifier_money_format
* Purpose:  pass a value to the PHP function money_format
* Usage:    e.g. {$number|money_format:2:,:.}
* -------------------------------------------------------------
*/
function smarty_modifier_money_format($number, $leftFill='0') {
  if (function_exists('money_format')) {
    setlocale(LC_MONETARY, 'de_DE.UTF8');
    $format = (!empty($leftFill))?'%!#'.$leftFill.'n':'%!n';
    return str_replace(' ','&nbsp;',money_format($format, $number));
  }
  else {
    return $number;
  }
}
?>