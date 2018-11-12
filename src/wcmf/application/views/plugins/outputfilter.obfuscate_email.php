<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2018 wemove digital solutions GmbH
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
  $encodePercentage = 50;

  // encoding functions
  $alwaysEncode = ['.', '@', '+'];
  $octHexEncodeChar = function($char) use ($alwaysEncode, $encodePercentage) {
    if (in_array($char, $alwaysEncode) || mt_rand(1, 100) < $encodePercentage) {
      if (mt_rand(0, 1)) {
        return '&#'.ord($char).';';
      }
      else {
        return '&#x'.dechex(ord($char)).';';
      }
    }
    else {
      return $char;
    }
  };
  $neverEncode = ['.', '@', '+']; // don't encode those as not fully supported by IE & Chrome
  $urlEncodeChar = function($char) use ($neverEncode, $encodePercentage) {
    if (!in_array($char, $neverEncode) && mt_rand(1, 100) < $encodePercentage) {
      $charCode = ord($char);
      return '%'.dechex(($charCode >> 4) & 0xF).dechex($charCode & 0xF);
    }
    else {
      return $char;
    }
  };

  // encode
  $output = preg_replace_callback(
    '!<a\s([^>]*)href=["\']mailto:([^"\'\?]+)([^"\']*)["\']([^>]*)>(.*?)</a[^>]*>!is',
    function($matches) use ($octHexEncodeChar, $urlEncodeChar) {
      // $matches[0] contains full matched string: <a href="...">...</a>
      // $matches[1] contains additional parameters
      // $matches[2] contains the email address which was specified as href
      // $matches[3] contains parameters such as subject and body
      // $matches[4] contains additional parameters
      // $matches[5] contains the text between the opening and closing <a> tag

      $address = $matches[2];
      // urlencode address
      $encodedAddress = preg_replace_callback('/./', function($m) use ($urlEncodeChar) {
        return $urlEncodeChar($m[0]);
      }, $address);
      // obfuscate href
      $obfuscatedLink = preg_replace_callback('/./', function($m) use ($octHexEncodeChar) {
        return $octHexEncodeChar($m[0]);
      }, 'mailto:'.$encodedAddress);

      $params = trim($matches[3]);

      $extra = trim($matches[1]." ".$matches[4]);
      // tell search engines to ignore obfuscated uri
      if (!preg_match('/rel=["\']nofollow["\']/', $extra)) {
        $extra = trim($extra.' rel="nofollow"');
      }

      // obfuscate text
      $text = $matches[5];
      $obfuscatedText = preg_replace_callback('/./', function($m) use ($octHexEncodeChar) {
        return $octHexEncodeChar($m[0]);
      }, $text);

      $replace = '<a href="'.$obfuscatedLink.$params.'" '.$extra.'>'.$obfuscatedText.'</a>';

      return $replace;
    }, $output);
  return $output;
}
?>