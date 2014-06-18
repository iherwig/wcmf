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
* File:     function.configvalue.php
* Type:     function
* Name:     configvalue
* Purpose:  output a configuration value
*           or assign it to a smarty variable
* Usage:    e.g. {configvalue key="exportDir" section="cms"} or
*           {configvalue key="exportDir" section="cms" varname="exportDir"}
* -------------------------------------------------------------
*/
function smarty_function_configvalue($params, \Smarty_Internal_Template $template) {
  $config = ObjectFactory::getConfigurationInstance();
  $value = $config->getValue($params['key'], $params['section'], false);
  if (isset($params['varname'])) {
    $template->assign($params['varname'], $value);
  }
  else {
    echo $value;
  }
}
?>