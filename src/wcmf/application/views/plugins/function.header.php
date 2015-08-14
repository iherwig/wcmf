<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.header.php
* Type:     function
* Name:     header
* Purpose:  output the given header
* Usage:    e.g. {header value="Content-Type: text/javascript"}
* -------------------------------------------------------------
*/
function smarty_function_header($params, \Smarty_Internal_Template $template) {
  if (isset($params['value'])) {
    header($params['value']);
  }
}
?>