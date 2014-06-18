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
function smarty_block_assign_block($params, $content, \Smarty_Internal_Template $template, &$repeat) {
  if (!empty($content)) {
    $template->assign($params['var'], $content);
  }
}
?>