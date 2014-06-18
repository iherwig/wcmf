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
* File: prefilter.removeprids.php
* Type: prefilter
* Name: removeprids
* Purpose: Remove protected region ids (used by wCMFGenerator).
* -------------------------------------------------------------
*/
function smarty_prefilter_removeprids($tpl_source, \Smarty_Internal_Template $template) {
  // remove protected regions
  $tpl_source = preg_replace("/<!-- PROTECTED REGION .*? -->/U", "", $tpl_source);

  // remove any wCMFGenerator generated comments
  return preg_replace("/<!--.*?ChronosGenerator.*?-->/s", "", $tpl_source);
}
?>
