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

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     function.str_repeat.php
* Type:     function
* Name:     str_repeat
* Purpose:  repeat a string
* Usage:    e.g. {str_repeat str="-" count=3}
* -------------------------------------------------------------
*/
function smarty_function_str_repeat($params, &$smarty)
{
  echo str_repeat($params['str'], $params['count']);
}
?>