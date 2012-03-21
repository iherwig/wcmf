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
namespace wcmf\lib\presentation\smarty_plugins;

use wcmf\lib\config\InifileParser;

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.configvalue.php
* Type:     function
* Name:     configvalue
* Purpose:  output a configuration value (uses the getValue method of InifileParser)
*           or assign it to a smarty variable
* Usage:    e.g. {configvalue key="exportDir" section="cms"} or
*           {configvalue key="exportDir" section="cms" varname="exportDir"}
* -------------------------------------------------------------
*/
function smarty_function_configvalue($params, $smarty)
{
  $parser = InifileParser::getInstance();
  $value = $parser->getValue($params['key'], $params['section'], false);
  if (isset($params['varname'])) {
    $smarty->assign($params['varname'], $value);
  }
  else {
  	echo $value;
  }
}
?>