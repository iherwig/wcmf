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
* File:     function.count_items.php
* Type:     function
* Name:     count_items
* Purpose:  count the items of an array and assign it to a template
*           variable
* Usage:    e.g. {count_items varname="numNodes" array=$nodes}
* -------------------------------------------------------------
*/
function smarty_function_count_items($params, &$smarty)
{
  if (is_array($params['array']))
    $smarty->assign($params['varname'], sizeof($params['array']));
}
?>