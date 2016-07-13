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
    return str_replace(' ', '&nbsp;', money_format($format, $number));
  }
  else {
    return $number;
  }
}
?>