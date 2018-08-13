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
 * @see https://stackoverflow.com/questions/12592363/looking-for-a-php-only-email-address-obfuscator-function#12592364
 *
 * @param $output
 * @param $template Smarty_Internal_Template
 * @return String
 */
function smarty_outputfilter_obfuscate_email($output, Smarty_Internal_Template $template) {
  // don't encode those as not fully supported by IE & Chrome
  $neverEncode = array('.', '@', '+');
  $encodeChar = function($char) use ($neverEncode) {
    if (!in_array($char, $neverEncode) && mt_rand(1, 100) < 25) {
      $charCode = ord($char);
      return '%'.dechex(($charCode >> 4) & 0xF).dechex($charCode & 0xF);
    }
    else {
      return $char;
    }
  };

  $output = preg_replace_callback(
    '!<a\s([^>]*)href=["\']mailto:([^"\']+)["\']([^>]*)>(.*?)</a[^>]*>!is',
    function($matches) use ($encodeChar) {
      // $matches[0] contains full matched string: <a href="...">...</a>
      // $matches[1] contains additional parameters
      // $matches[2] contains the email address which was specified as href
      // $matches[3] contains additional parameters
      // $matches[4] contains the text between the opening and closing <a> tag

      $address = $matches[2];
      $obfuscatedAddress = preg_replace_callback('/./', function($m) use ($encodeChar) {
        return $encodeChar($m[0]);
      }, $address);

      $extra = trim($matches[1]." ".$matches[3]);
      // tell search engines to ignore obfuscated uri
      if (!preg_match('/rel=["\']nofollow["\']/', $extra)) {
        $extra = trim($extra.' rel="nofollow"');
      }
      $text = $matches[4];
      $obfuscatedText = preg_replace_callback('/./', function($m) use ($encodeChar) {
        return $encodeChar($m[0]);
      }, $text);

      $replace = '<a href="mailto:'.$obfuscatedAddress.'" '.$extra.'>'.urldecode($obfuscatedText).'</a>';

      return $replace;
    }, $output);
  return $output;
}
?>