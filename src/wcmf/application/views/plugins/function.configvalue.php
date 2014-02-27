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