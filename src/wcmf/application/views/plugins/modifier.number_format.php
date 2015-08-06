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
* File:     modifier.number_format.php
* Type:     function
* Name:     number_format
* Purpose:  pass a value to the PHP function number_format
* Usage:    e.g. {$number|number_format:2:,:.}
* -------------------------------------------------------------
*/
function smarty_modifier_number_format($number, $decimals="2", $dec_point=",", $thousands_sep=".") {
  return number_format($number, $decimals, $dec_point, $thousands_sep);
}
?>