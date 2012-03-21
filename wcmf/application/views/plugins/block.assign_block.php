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
* File:     block.assign_block.php
* Type:     block
* Name:     assign_block
* Purpose:  assign a multiline string to a variable
* Parameters: var [required] - the name of the variable
* Usage:    {assign_block var="myVar"}
*               ... string to
*               assign ...
*           {/assign_block}
*
* Author:   Ingo Herwig <ingo@wemove.com>
* -------------------------------------------------------------
*/
function smarty_block_assign_block($params, $content, &$smarty)
{
    if (!empty($content))
    {
      $smarty->assign($params['var'], $content);
    }
}
?>