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

