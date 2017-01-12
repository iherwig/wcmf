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
 * Remove closing slashes from void tags according to html5.
 *
 * @param $output
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_outputfilter_html5_void_tags($output, Smarty_Internal_Template $template) {
  // remove slashes from image tags
  $output = preg_replace('/<img([^>]+) ?\/\>/i', "<img $1>", $output);
  // replace brs
  $output = preg_replace("/<br ?\/\>/i", "<br>", $output);

  return $output;
}
?>