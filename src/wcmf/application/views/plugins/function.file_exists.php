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