<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.file_exists.php
* Type:     function
* Name:     file_exists
* Purpose:  check if a given file exists and assign the result to a smarty variable
* Usage:    {file_exists file="images/test.png" varname="imageExists"}
* -------------------------------------------------------------
*/
function smarty_function_file_exists($params, \Smarty_Internal_Template $template) {
  $template->assign($params['varname'], file_exists($params['file']));
}
?>