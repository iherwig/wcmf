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