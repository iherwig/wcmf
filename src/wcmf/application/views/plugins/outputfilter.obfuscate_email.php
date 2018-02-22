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
 * @see https://stackoverflow.com/questions/42124369/php-obfuscating-mailto-in-source-with-htmlentities
 *
 * @param $output
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_outputfilter_obfuscate_email($output, Smarty_Internal_Template $template) {
  $output = preg_replace_callback(
    '!<a\s([^>]*)href=["\']mailto:([^"\']+)["\']([^>]*)>(.*?)</a[^>]*>!is',
    function($matches) {
      // $matches[0] contains full matched string: <a href="...">...</a>
      // $matches[1] contains additional parameters
      // $matches[2] contains the email address which was specified as href
      // $matches[3] contains additional parameters
      // $matches[4] contains the text between the opening and closing <a> tag

      $address = $matches[2];
      $obfuscatedAddress = preg_replace_callback('/./', function($m) {
        return '&#'.ord($m[0]).';';
      }, $address);

      $extra = trim($matches[1]." ".$matches[3]);
      $text = $matches[4];
      $obfuscatedText = preg_replace_callback('/./', function($m) {
        return '&#'.ord($m[0]).';';
      }, $text);

      $replace = '<a href="mailto:'.$obfuscatedAddress.'">'.$obfuscatedText.'</a>';

      return $replace;
    }, $output);
  return $output;
}
?>