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
namespace wcmf\lib\util;

/**
 * StringUtil provides support for string manipulation.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class StringUtil {

  /**
   * Get the dump of a variable as string.
   * code from: https://www.leaseweb.com/labs/2013/10/smart-alternative-phps-var_dump-function/
   * @param $variable Variable to dump.
   * @param $strlen Max length of characters of each string to display (full length is shown)
   * @param $width Max number of elements of an array to display (full length is shown)
   * @param $depth Max number of levels of nested objects/array to display
   * @return String
   */
  public static function getDump($variable, $strlen=100, $width=25, $depth=10, $i=0, &$objects = []) {
    $search = ["\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v"];
    $replace = ['\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v'];

    $string = '';

    switch(gettype($variable)) {
      case 'boolean':
        $string .= $variable ? 'true' : 'false';
        break;
      case 'integer':
        $string .= $variable;
        break;
      case 'double':
        $string .= $variable;
        break;
      case 'resource':
        $string .= '[resource]';
        break;
      case 'NULL':
        $string .= "null";
        break;
      case 'unknown type':
        $string .= '???';
        break;
      case 'string':
        $len = strlen($variable);
        $variable = str_replace($search, $replace, substr($variable, 0, $strlen), $count);
        $variable = substr($variable, 0, $strlen);
        if ($len < $strlen) {
          $string .= '"'.$variable.'"';
        }
        else {
          $string .= 'string('.$len.'): "'.$variable.'"...';
        }
        break;
      case 'array':
        $len = count($variable);
        if ($i == $depth) {
          $string .= 'array('.$len.') {...}';
        }
        elseif (!$len) {
          $string .= 'array(0) {}';
        }
        else {
          $keys = array_keys($variable);
          $spaces = str_repeat(' ', $i*2);
          $string .= "array($len)\n".$spaces.'{';
          $count=0;
          foreach ($keys as $key) {
            if ($count == $width) {
              $string .= "\n".$spaces."  ...";
              break;
            }
            $string .= "\n".$spaces."  [$key] => ";
            $string .= self::getDump($variable[$key], $strlen, $width, $depth, $i+1, $objects);
            $count++;
          }
          $string .="\n".$spaces.'}';
        }
        break;
      case 'object':
        $id = array_search($variable, $objects, true);
        if ($id !== false) {
          $string .= get_class($variable).'#'.($id+1).' {...}';
        }
        else if ($i == $depth) {
          $string .= get_class($variable).' {...}';
        }
        else {
          $id = array_push($objects, $variable);
          $array = (array)$variable;
          $spaces = str_repeat(' ', $i*2);
          $string .= get_class($variable)."#$id\n".$spaces.'{';
          $properties = array_keys($array);
          foreach ($properties as $property) {
            $name = str_replace("\0", ':', trim($property));
            $string .= "\n".$spaces."  [$name] => ";
            $string .= self::getDump($array[$property], $strlen, $width, $depth, $i+1, $objects);
          }
          $string .= "\n".$spaces.'}';
        }
        break;
      }
    }

    if ($i>0) {
      return $string;
    }
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    do {
      $caller = array_shift($backtrace);
    }
    while ($caller && !isset($caller['file']));
    if ($caller) {
      $string = $caller['file'].':'.$caller['line']."\n".$string;
    }
    return $string;
  }

  /**
   * Truncate a string up to a number of characters while preserving whole words and HTML tags
   * code based on: http://www.dzone.com/snippets/truncate-text-preserving-html
   * @param $text String to truncate.
   * @param $length Length of returned string, excluding suffix.
   * @param $suffix Ending to be appended to the trimmed string.
   * @param $isHTML If true, HTML tags would be handled correctly
   * @return String
   */
  public static function cropString($text, $length=100, $suffix='…', $isHTML=true) {
    $i = 0;
    $simpleTags=['br'=>true,'hr'=>true,'input'=>true,'image'=>true,'link'=>true,'meta'=>true];
    $tags = [];
    if($isHTML) {
      preg_match_all('/<[^>]+>([^<]*)/', $text, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
      foreach($m as $o) {
        if($o[0][1] - $i >= $length) {
          break;
        }
        $t = substr(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);
        // test if the tag is unpaired, then we mustn't save them
        if($t[0] != '/' && (!isset($simpleTags[$t]))) {
          $tags[] = $t;
        }
        elseif(end($tags) == substr($t, 1)) {
          array_pop($tags);
        }
        $i += $o[1][1] - $o[0][1];
      }
    }
    // output without closing tags
    $output = substr($text, 0, $length = min(strlen($text), $length + $i));
    // closing tags
    $output2 = (count($tags = array_reverse($tags)) ? '' : '');
    // Find last space or HTML tag (solving problem with last space in HTML tag eg. )
    $pos = @(int)end(end(preg_split('/<.*>| /', $output, -1, PREG_SPLIT_OFFSET_CAPTURE)));
    // Append closing tags to output
    $output.=$output2;
    // Get everything until last space
    $one = substr($output, 0, $pos);
    // Get the rest
    $two = substr($output, $pos, (strlen($output) - $pos));
    // Extract all tags from the last bit
    preg_match_all('/<(.*?)>/s', $two, $tags);
    // Add suffix if needed
    if (strlen($text) > $length) {
      $one .= $suffix;
    }
    // Re-attach tags
    $output = $one . implode($tags[0]);
    // Added to remove unnecessary closure
    $output = str_replace('', '', $output);
    return $output;
  }

  /**
   * Create an excerpt from the given text around the given phrase
   * code based on: http://stackoverflow.com/questions/1292121/how-to-generate-the-snippet-like-generated-by-google-with-php-and-mysql
   * @param $string
   * @param $phrase
   * @param $radius
   */
  public static function excerpt($string, $phrase, $radius = 100) {
    if ($radius > strlen($string)) {
      return $string;
    }
    $phraseLen = strlen($phrase);
    if ($radius < $phraseLen) {
        $radius = $phraseLen;
    }
    $pos = strpos(strtolower($string), strtolower($phrase));

    $startPos = 0;
    if ($pos > $radius) {
      $startPos = $pos - $radius;
    }
    $textLen = strlen($string);

    $endPos = $pos + $phraseLen + $radius;
    if ($endPos >= $textLen) {
      $endPos = $textLen;
    }

    // make sure to cut at spaces
    $firstSpacePos = strpos($string, " ", $startPos);
    $lastSpacePos = strrpos($string, " ", -(strlen($string)-$endPos));

    $excerpt1 = substr($string, $firstSpacePos, $lastSpacePos-$firstSpacePos);

    // remove open tags
    $excerpt = preg_replace('/^[^<]*?>|<[^>]*?$/', '', $excerpt1);
    return $excerpt;
  }

  /**
   * Extraxt urls from a string.
   * @param $string The string to search in
   * @return An array with urls
   * @note This method searches for occurences of <a..href="xxx"..>, <img..src="xxx"..>, <video..src="xxx"..>,
   * <audio..src="xxx"..>, <input..src="xxx"..>, <form..action="xxx"..>, <link..href="xxx"..>, <script..src="xxx"..>
   * and extracts xxx.
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
    preg_match_all("/<video[^>]+src=\"([^\">]+)/i", $string, $videos);
    preg_match_all("/<audios[^>]+src=\"([^\">]+)/i", $string, $audios);
    preg_match_all("/<input[^>]+src=\"([^\">]+)/i", $string, $buttons);
    preg_match_all("/<form[^>]+action=\"([^\">]+)/i", $string, $actions);
    preg_match_all("/<link[^>]+href=\"([^\">]+)/i", $string, $css);
    preg_match_all("/<script[^>]+src=\"([^\">]+)/i", $string, $scripts);
    return array_merge($links[1], $images[1], $videos[1], $audios[1], $buttons[1], $actions[1], $css[1], $scripts[1]);
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
   * @param $string The string to split
   * @return An array of strings
   */
  public static function quotesplit($string) {
    $r = [];
    $p = 0;
    $l = strlen($string);
    while ($p < $l) {
      while (($p < $l) && (strpos(" \r\t\n", $string[$p]) !== false)) {
        $p++;
      }
      if ($string[$p] == '"') {
        $p++;
        $q = $p;
        while (($p < $l) && ($string[$p] != '"')) {
          if ($string[$p] == '\\') {
            $p+=2;
            continue;
          }
          $p++;
        }
        $r[] = stripslashes(substr($string, $q, $p-$q));
        $p++;
        while (($p < $l) && (strpos(" \r\t\n", $string[$p]) !== false)) {
          $p++;
        }
        $p++;
      }
      else if ($string[$p] == "'") {
        $p++;
        $q = $p;
        while (($p < $l) && ($string[$p] != "'")) {
          if ($string[$p] == '\\') {
            $p+=2;
            continue;
          }
          $p++;
        }
        $r[] = stripslashes(substr($string, $q, $p-$q));
        $p++;
        while (($p < $l) && (strpos(" \r\t\n", $string[$p]) !== false)) {
          $p++;
        }
        $p++;
      }
      else {
        $q = $p;
        while (($p < $l) && (strpos(",;", $string[$p]) === false)) {
          $p++;
        }
        $r[] = stripslashes(trim(substr($string, $q, $p-$q)));
        while (($p < $l) && (strpos(" \r\t\n", $string[$p]) !== false)) {
          $p++;
        }
        $p++;
      }
    }
    return $r;
  }

  /**
   * Split string preserving quoted strings
   * code based on: http://www.php.net/manual/en/function.explode.php#94024
   * @param $string String to split
   * @param $delim Regexp to use in preg_split
   * @param $quoteChr Quote character
   * @param $preserve Boolean whether to preserve the quote character or not
   * @return Array
   */
  public static function splitQuoted($string, $delim='/ /', $quoteChr='"', $preserve=false){
    $resArr = [];
    $n = 0;
    $expEncArr = explode($quoteChr, $string);
    foreach($expEncArr as $encItem) {
      if ($n++%2) {
        $resArr[] = array_pop($resArr) . ($preserve?$quoteChr:'') . $encItem.($preserve?$quoteChr:'');
      }
      else {
        $expDelArr = preg_split($delim, $encItem);
        $resArr[] = array_pop($resArr) . array_shift($expDelArr);
        $resArr = array_merge($resArr, $expDelArr);
      }
    }
    return $resArr;
  }

  /**
   * Convert a string in underscore notation to camel case notation.
   * Code from http://snipt.net/hongster/underscore-to-camelcase/
   * @param $string The string to convert
   * @param $firstLowerCase Boolean whether the first character should be lowercase or not (default: _false_)
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
   * @param $string The string
   * @return The escaped string
   */
  public static function escapeForRegex($string) {
    $patterns = ['/\//', '/\^/', '/\./', '/\$/', '/\|/', '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/', '/\?/', '/\{/', '/\}/'];
    $replace = ['\/', '\^', '\.', '\$', '\|', '\(', '\)', '\[', '\]', '\*', '\+', '\?', '\{', '\}'];

    return preg_replace($patterns, $replace, $string);
  }

  /**
   * Remove a trailing comma, if existing.
   * @param $string The string to crop
   * @return The string
   */
  public static function removeTrailingComma($string) {
    return preg_replace('/, ?$/', '', $string);
  }

  /**
   * Get the boolean value of a string
   * @param $string
   * @return Boolean or the string, if it does not represent a boolean.
   */
  public static function getBoolean($string) {
    $val = filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($val === null) {
      return $string;
    }
    return $val;
  }

  /**
   * Converts all accent characters to ASCII characters.
   * Code from http://stackoverflow.com/questions/2103797/url-friendly-username-in-php/2103815#2103815
   * @param $string Text that might have accent characters
   * @return string Filtered string with replaced "nice" characters.
   */
  public static function slug($string) {
    $search = ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'];
    $replace = ['AE', 'OE', 'UE', 'ae', 'oe', 'ue', 'ss'];
    $string = str_replace($search, $replace, $string);
    return strtolower(trim(preg_replace('~[^0-9a-z]+~i', '-',
            html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1',
                    htmlentities($string, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
  }
}
?>
