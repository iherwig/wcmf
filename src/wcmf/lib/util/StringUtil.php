<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2020 wemove digital solutions GmbH
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
   * @param mixed $variable Variable to dump.
   * @param int $strlen Max length of characters of each string to display (full length is shown)
   * @param int $width Max number of elements of an array to display (full length is shown)
   * @param int $depth Max number of levels of nested objects/array to display
   * @param array<mixed> $objects Internal use
   * @return string
   */
  public static function getDump($variable, int $strlen=100, int $width=25, int $depth=10, int $i=0, array &$objects=[]): string {
    $search = ["\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v"];
    $replace = ['\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v'];

    $string = '';

    switch (gettype($variable)) {
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
          $string .= get_class($variable).'#'.(intval($id)+1).' {...}';
        }
        elseif ($i == $depth) {
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
   * Truncate a string up to a number of characters while preserving whole words and HTML tags.
   * Based on https://stackoverflow.com/questions/16583676/shorten-text-without-splitting-words-or-breaking-html-tags#answer-16584383
   * @param string $text String to truncate.
   * @param int $length Length of returned string (optional, default: 100)
   * @param string $suffix Ending to be appended to the trimmed string (optional, default: …)
   * @param bool $exact Boolean whether to allow to cut inside a word or not (optional, default: false)
   * @return string
   */
  public static function cropString(string $text, int $length=100, string $suffix='…', bool $exact=false): string {
    if (strlen($text) <= $length) {
      return $text;
    }

    $isHtml = strip_tags($text) !== $text;

    $dom = new \DomDocument();
    $dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $reachedLimit = false;
    $totalLen = 0;
    $toRemove = [];
    $walk = function(\DomNode $node) use (&$reachedLimit, &$totalLen, &$toRemove, &$walk, $length, $suffix, $exact): array {
      if ($reachedLimit) {
        $toRemove[] = $node;
      }
      else {
        // only text nodes should have text,
        // so do the splitting here
        if ($node instanceof \DomText) {
          $totalLen += $nodeLen = strlen($node->nodeValue);
          if ($totalLen > $length) {
            $spacePos = strpos($node->nodeValue, ' ', $nodeLen-($totalLen-$length)-1);
            $node->nodeValue = $exact ? substr($node->nodeValue, 0, intval($nodeLen-($totalLen-$length))) : substr($node->nodeValue, 0, intval($spacePos));
            // don't add suffix to empty node
            $node->nodeValue .= (strlen($node->nodeValue) > 0 ? $suffix : '');
            $reachedLimit = true;
          }
        }

        // if node has children, walk its child elements
        foreach ($node->childNodes as $child) {
          $walk($child);
        }
      }
      /** @var \DOMNode[] $toRemove */
      return $toRemove;
    };

    // remove any nodes that exceed limit
    $toRemove = $walk($dom);
    foreach ($toRemove as $child) {
      if ($child->parentNode != null) {
        $child->parentNode->removeChild($child);
      }
    }

    $result = strval($dom->saveHTML());
    return $isHtml ? $result : html_entity_decode(strip_tags($result));
  }

  /**
   * Create an excerpt from the given text around the given phrase
   * code based on: http://stackoverflow.com/questions/1292121/how-to-generate-the-snippet-like-generated-by-google-with-php-and-mysql
   * @param string $string
   * @param string $phrase
   * @param int $radius
   */
  public static function excerpt(string $string, string $phrase, int $radius=100): string {
    if ($radius > strlen($string)) {
      return $string;
    }
    $phraseLen = strlen($phrase);
    if ($radius < $phraseLen) {
        $radius = $phraseLen;
    }
    $pos = intval(strpos(strtolower($string), strtolower($phrase)));

    $startPos = 0;
    if ($pos > $radius) {
      $startPos = $pos-$radius;
    }
    $textLen = strlen($string);

    $endPos = $pos+$phraseLen+$radius;
    if ($endPos >= $textLen) {
      $endPos = $textLen;
    }

    // make sure to cut at spaces
    $firstSpacePos = intval(strpos($string, " ", $startPos));
    $lastSpacePos = intval(strrpos($string, " ", -(strlen($string)-$endPos)));

    $excerpt1 = substr($string, $firstSpacePos, $lastSpacePos-$firstSpacePos);

    // remove open tags
    $excerpt = strval(preg_replace('/^[^<]*?>|<[^>]*?$/', '', $excerpt1));
    return $excerpt;
  }

  /**
   * Extraxt urls from a string.
   * @param string $string The string to search in
   * @return array<string> with urls
   * @note This method searches for occurences of <a..href="xxx"..>, <img..src="xxx"..>, <video..src="xxx"..>,
   * <audio..src="xxx"..>, <input..src="xxx"..>, <form..action="xxx"..>, <link..href="xxx"..>, <script..src="xxx"..>
   * and extracts xxx.
   */
  public static function getUrls(string $string): array {
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
   * @param string $string The string to split
   * @return array<string>
   */
  public static function quotesplit(string $string): array {
    $r = [];
    $p = 0;
    $l = strlen($string);
    $sep = " \r\t\n";
    while ($p < $l) {
      while (($p < $l) && (strpos($sep, $string[$p]) !== false)) {
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
        while (($p < $l) && (strpos($sep, $string[$p]) !== false)) {
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
        while (($p < $l) && (strpos($sep, $string[$p]) !== false)) {
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
        while (($p < $l) && (strpos($sep, $string[$p]) !== false)) {
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
   * @param string $string String to split
   * @param string $delim Regexp to use in preg_split
   * @param string $quoteChr Quote character
   * @param bool $preserve Boolean whether to preserve the quote character or not
   * @return array<string>
   */
  public static function splitQuoted(string $string, string $delim='/ /', string $quoteChr='"', bool $preserve=false): array {
    $resArr = [];
    $n = 0;
    /** @var string[] $expEncArr */
    $expEncArr = explode($quoteChr, $string);
    foreach($expEncArr as $encItem) {
      if ($n++%2) {
        $resArr[] = array_pop($resArr) . ($preserve?$quoteChr:'') . $encItem.($preserve?$quoteChr:'');
      }
      else {
        /** @var string[] $expDelArr */
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
   * @param string $string The string to convert
   * @param bool $firstLowerCase Boolean whether the first character should be lowercase or not (default: _false_)
   * @return string
   */
  public static function underScoreToCamelCase(string $string, bool $firstLowerCase=false): string {
    $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    if ($firstLowerCase) {
      $str[0] = strtolower($str[0]);
    }
    return $str;
  }

  /**
   * Escape characters of a string for use in a regular expression
   * Code from http://php.net/manual/de/function.preg-replace.php
   * @param string $string The string
   * @return string
   */
  public static function escapeForRegex(string $string): string {
    $patterns = ['/\//', '/\^/', '/\./', '/\$/', '/\|/', '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/', '/\?/', '/\{/', '/\}/'];
    $replace = ['\/', '\^', '\.', '\$', '\|', '\(', '\)', '\[', '\]', '\*', '\+', '\?', '\{', '\}'];

    return strval(preg_replace($patterns, $replace, $string));
  }

  /**
   * Remove a trailing comma, if existing.
   * @param string $string The string to crop
   * @return string
   */
  public static function removeTrailingComma(string $string): string {
    return strval(preg_replace('/, ?$/', '', $string));
  }

  /**
   * Get the boolean value of a string
   * @param string $string
   * @return mixed Boolean or the string, if it does not represent a boolean.
   */
  public static function getBoolean(string $string) {
    $val = filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($val === null) {
      return $string;
    }
    return $val;
  }

  /**
   * Converts all accent characters to ASCII characters.
   * Code from http://stackoverflow.com/questions/2103797/url-friendly-username-in-php/2103815#2103815
   * @param string $string Text that might have accent characters
   * @return string Filtered string with replaced "nice" characters.
   */
  public static function slug(string $string): string {
    $search = ['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'];
    $replace = ['AE', 'OE', 'UE', 'ae', 'oe', 'ue', 'ss'];
    $string = str_replace($search, $replace, $string);
    return strtolower(trim(strval(preg_replace('~[^0-9a-z]+~i', '-',
            html_entity_decode(strval(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1',
                    htmlentities($string, ENT_QUOTES, 'UTF-8'))), ENT_QUOTES, 'UTF-8'))), '-'));
  }

  /**
   * Generate a v4 UUID
   * Code from https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid#15875555
   * @return string
   */
  public static function guidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }
}
?>
