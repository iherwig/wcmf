<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2017 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */

/**
 * Remove protected region ids (used by wCMFGenerator).
 *
 * @param $output
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_prefilter_removeprids($output, Smarty_Internal_Template $template) {
  // remove protected regions
  $output = preg_replace("/<!-- PROTECTED REGION .*? -->/U", "", $output);

  // remove any wCMFGenerator generated comments
  return preg_replace("/<!--.*?ChronosGenerator.*?-->/s", "", $output);
}
?>
