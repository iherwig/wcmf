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
 * $Id: outputfilter.html5_void_tags.php 1164 2010-04-07 12:55:00Z iherwig $
 */

/*
* Smarty output filter
* -------------------------------------------------------------
* File:     outputfilter.html5_void_tags.php
* Type:     function
* Name:     html5_void_tags
* Purpose:  remove closing slashes from void tags according to html5
* -------------------------------------------------------------
*/
function smarty_outputfilter_html5_void_tags($output, \Smarty_Internal_Template $template) {
  // remove slashes from image tags
  $output = preg_replace('/<img([^>]+) ?\/\>/i', "<img $1>", $output);
  // replace brs
  $output = preg_replace("/<br ?\/\>/i", "<br>", $output);

  return $output;
}
?>