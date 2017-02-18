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
 * Obfuscate email addresses.
 * @see http://www.phpinsider.com/smarty-forum/viewtopic.php?t=2166
 *
 * @global $obfuscated_email_count
 * @param $output
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_outputfilter_obfuscate_email($output, Smarty_Internal_Template $template) {
  $obfuscated_email_count = 0;
  $output = preg_replace_callback(
    '!<a\s([^>]*)href=["\']mailto:([^"\']+)["\']([^>]*)>(.*?)</a[^>]*>!is',
    function($matches) use (&$obfuscated_email_count) {
      // $matches[0] contains full matched string: <a href="...">...</a>
      // $matches[1] contains additional parameters
      // $matches[2] contains the email address which was specified as href
      // $matches[3] contains additional parameters
      // $matches[4] contains the text between the opening and closing <a> tag

      $address = $matches[2];
      $obfuscated_address = str_replace([".","@"], [" dot ", " at "], $address);
      $extra = trim($matches[1]." ".$matches[3]);
      $text = $matches[4];
      $obfuscated_text = str_replace([".","@"], [" dot ", " at "], $text);

      $string = "var e; if (e = document.getElementById('obfuscated_email_".$obfuscated_email_count."')) e.style.display = 'none';\n";
      $string .= "document.write('<a href=\"mailto:".$address."\" ".$extra.">".$text."</a>');";
      $js_encode = '';
      for ($x=0; $x < strlen($string); $x++) {
        $js_encode .= '%' . bin2hex($string[$x]);
      }
      $replace = '<a id="obfuscated_email_'.$obfuscated_email_count.'" href="mailto:'.$obfuscated_address.'">'.$obfuscated_text.'</a><script type="text/javascript" language="javascript">eval(unescape(\''.$js_encode.'\'))</script>';

      ++$obfuscated_email_count;

      return $replace;
    },
    $output);
  return $output;
}
?>