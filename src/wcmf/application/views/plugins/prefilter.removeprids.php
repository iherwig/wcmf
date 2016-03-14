<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2015 wemove digital solutions GmbH
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
function smarty_prefilter_removeprids($tplSource, \Smarty_Internal_Template $template) {
  // remove protected regions
  $tplSource = preg_replace("/<!-- PROTECTED REGION .*? -->/U", "", $tplSource);

  // remove any wCMFGenerator generated comments
  return preg_replace("/<!--.*?ChronosGenerator.*?-->/s", "", $tplSource);
}
?>
