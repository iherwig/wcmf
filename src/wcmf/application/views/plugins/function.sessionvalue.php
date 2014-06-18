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
use wcmf\lib\core\ObjectFactory;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.sessionvalue.php
* Type:     function
* Name:     sessionvalue
* Purpose:  output a session variable value
* Usage:    e.g. {sessionvalue name="platform"} or
*           {sessionvalue name="platform" varname="platform"}
* -------------------------------------------------------------
*/
function smarty_function_sessionvalue($params, \Smarty_Internal_Template $template) {
  $session = ObjectFactory::getInstance('session');
  $value = $session->get($params['name']);
  if (isset($params['varname'])) {
    $template->assign($params['varname'], $value);
  }
  else {
    echo $value;
  }
}
?>