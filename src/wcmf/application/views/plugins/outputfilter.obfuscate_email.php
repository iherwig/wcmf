<?php
 /**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
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
 */

/*
* Smarty plugin
* -------------------------------------------------------------
* File:     smarty_outputfilter_obfuscate_email
* code from: http://www.phpinsider.com/smarty-forum/viewtopic.php?t=2166
* -------------------------------------------------------------
*/
function smarty_outputfilter_obfuscate_email($output, \Smarty_Internal_Template $template) {
  global $obfuscated_email_count;
  $obfuscated_email_count = 0;
  $output = preg_replace_callback(
    '!<a\s([^>]*)href=["\']mailto:([^"\']+)["\']([^>]*)>(.*?)</a[^>]*>!is',
    'do_it',
    $output);
  return $output;
}

function do_it($matches) {
  global $obfuscated_email_count;

  // $matches[0] contains full matched string: <a href="...">...</a>
  // $matches[1] contains additional parameters
  // $matches[2] contains the email address which was specified as href
  // $matches[3] contains additional parameters
  // $matches[4] contains the text between the opening and closing <a> tag

  $address = $matches[2];
  $obfuscated_address = str_replace(array(".","@"), array(" dot ", " at "), $address);
  $extra = trim($matches[1]." ".$matches[3]);
  $text = $matches[4];
  $obfuscated_text = str_replace(array(".","@"), array(" dot ", " at "), $text);

  $string = "var e; if (e = document.getElementById('obfuscated_email_".$obfuscated_email_count."')) e.style.display = 'none';\n";
  $string .= "document.write('<a href=\"mailto:".$address."\" ".$extra.">".$text."</a>');";
  $js_encode = '';
  for ($x=0; $x < strlen($string); $x++) {
    $js_encode .= '%' . bin2hex($string[$x]);
  }
  $replace = '<a id="obfuscated_email_'.$obfuscated_email_count.'" href="mailto:'.$obfuscated_address.'">'.$obfuscated_text.'</a><script type="text/javascript" language="javascript">eval(unescape(\''.$js_encode.'\'))</script>';

  ++$obfuscated_email_count;

  return $replace;
}
?>