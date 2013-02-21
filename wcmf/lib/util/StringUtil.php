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
 * $Id$
 */
namespace wcmf\lib\util;

/**
 * StringUtil provides support for string manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringUtil {

  /**
   * Get the dump of a variable as string.
   * @param var The variable to dump.
   * @return String
   */
  public static function getDump ($var) {
    ob_start();
    var_dump($var);
    $out = ob_get_contents();
    ob_end_clean();
    return $out;
  }

  /**
   * Shorten a string to a given length.
   * @param string The string to crop
   * @param length The length of the string to display (if no length is given, complete string is displayed),
   *               string will be cropped and filled with " ..."
   * @return The cropped string
   */
  public static function cropString($string, $length=-1) {
    if ($length != -1 && strlen($string) > $length) {
      $string = substr($string, 0, $length-4)." ...";
    }
    return $string;
  }

  /**
   * Remove a trailing comma, if existing.
   * @param string The string to crop
   * @return The string
   */
  public static function removeTrailingComma($string) {
    return preg_replace('/, ?$/', '', $string);
  }

  /**
   * Extraxt urls from a string.
   * @param string The string to search in
   * @return An array with urls
   * @note This method searches for occurences of <a..href="xxx"..>, <img..src="xxx"..>,
   * <input..src="xxx"..> or <form..action="xxx"..> and extracts xxx.
   */
  public static function getUrls($string) {
    preg_match_all("/<a[^>]+href=\"([^\">]+)/i", $string, $links);

    // find urls in javascript popup links
    for ($i=0; $i<sizeof($links[1]); $i++) {
      if (preg_match_all("/javascript:.*window.open[\(]*'([^']+)/i", $links[1][$i], $popups)) {
        $links[1][$i] = $popups[1][0];
      }
    }
    // remove mailto links
    for ($i=0; $i<sizeof($links[1]); $i++) {
      if (preg_match("/^mailto:/i", $links[1][$i])) {
        unset($links[1][$i]);
      }
    }
    preg_match_all("/<img[^>]+src=\"([^\">]+)/i", $string, $images);
    preg_match_all("/<input[^>]+src=\"([^\">]+)/i", $string, $buttons);
    preg_match_all("/<form[^>]+action=\"([^\">]+)/i", $string, $actions);
    return array_merge($links[1], $images[1], $buttons[1], $actions[1]);
  }

  /**
   * Split a quoted string
   * code from: http://php3.de/manual/de/function.split.php
   * @code
   * $string = '"hello, world", "say \"hello\"", 123, unquotedtext';
   * $result = quotsplit($string);
   *
   * // results in:
   * // ['hello, world'] [say "hello"] [123] [unquotedtext]
   *
   * @endcode
   *
   * @param string The string to split
   * @return An array of strings
   */
  public static function quotesplit($string) {
    $r = Array();
    $p = 0;
    $l = strlen($string);
    while ($p < $l) {
      while (($p < $l) && (strpos(" \r\t\n",$string[$p]) !== false)) $p++;
      if ($string[$p] == '"') {
        $p++;
        $q = $p;
        while (($p < $l) && ($string[$p] != '"')) {
          if ($string[$p] == '\\') { $p+=2; continue; }
          $p++;
        }
        $r[] = stripslashes(substr($string, $q, $p-$q));
        $p++;
        while (($p < $l) && (strpos(" \r\t\n",$string[$p]) !== false)) $p++;
        $p++;
      }
      else if ($string[$p] == "'") {
        $p++;
        $q = $p;
        while (($p < $l) && ($string[$p] != "'")) {
          if ($string[$p] == '\\') { $p+=2; continue; }
          $p++;
        }
        $r[] = stripslashes(substr($string, $q, $p-$q));
        $p++;
        while (($p < $l) && (strpos(" \r\t\n",$string[$p]) !== false)) $p++;
        $p++;
      }
      else {
        $q = $p;
        while (($p < $l) && (strpos(",;",$string[$p]) === false)) {
          $p++;
        }
        $r[] = stripslashes(trim(substr($string, $q, $p-$q)));
        while (($p < $l) && (strpos(" \r\t\n",$string[$p]) !== false)) $p++;
        $p++;
      }
    }
    return $r;
  }

  /**
   * Convert a string in underscore notation to camel case notation.
   * Code from http://snipt.net/hongster/underscore-to-camelcase/
   * @param string The string to convert
   * @param firstLowerCase True/False wether the first character should be lowercase or not [default: false]
   * @return The converted string
   */
  public static function underScoreToCamelCase($string, $firstLowerCase=false) {
    if (is_string($string)) {
      $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
      if ($firstLowerCase) {
        $str{0} = strtolower($str{0});
      }
      return $str;
    }
    else {
      return '';
    }
  }

  /**
   * Escape characters of a string for use in a regular expression
   * Code from http://php.net/manual/de/function.preg-replace.php
   * @param string The string
   * @return The escaped string
   */
  public static function escapeForRegex($string) {
    $patterns = array('/\//', '/\^/', '/\./', '/\$/', '/\|/', '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/', '/\?/', '/\{/', '/\}/');
    $replace = array('\/', '\^', '\.', '\$', '\|', '\(', '\)', '\[', '\]', '\*', '\+', '\?', '\{', '\}');

    return preg_replace($patterns, $replace, $string);
  }

  /**
   * Get the boolean value of a string
   * @param string
   * @return Boolean or the string, if it does not represent a boolean.
   */
  public static function getBoolean($string) {
    $val = filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($val === null) {
      return $string;
    }
    return $val;
  }
}
?>
