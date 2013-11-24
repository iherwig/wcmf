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
   * Truncate a string up to a number of characters while preserving whole words and HTML tags
   * code from: http://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
   * @param text String to truncate.
   * @param length Length of returned string, including ellipsis.
   * @param ending Ending to be appended to the trimmed string.
   * @param exact If false, $text will not be cut mid-word
   * @param considerHtml If true, HTML tags would be handled correctly
   * @return String
   */
  public static function cropString($text, $length=100, $ending='...', $exact=false, $considerHtml=true) {
    if ($considerHtml) {
      // if the plain text is shorter than the maximum length, return the whole text
      if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
        return $text;
      }
      // splits all html-tags to scanable lines
      preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
      $total_length = strlen($ending);
      $open_tags = array();
      $truncate = '';
      foreach ($lines as $line_matchings) {
        // if there is any html-tag in this line, handle it and add it (uncounted) to the output
        if (!empty($line_matchings[1])) {
          // if it's an "empty element" with or without xhtml-conform closing slash
          if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
            // do nothing
          // if tag is a closing tag
          } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
            // delete tag from $open_tags list
            $pos = array_search($tag_matchings[1], $open_tags);
            if ($pos !== false) {
            unset($open_tags[$pos]);
            }
          // if tag is an opening tag
          } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
            // add tag to the beginning of $open_tags list
            array_unshift($open_tags, strtolower($tag_matchings[1]));
          }
          // add html-tag to $truncate'd text
          $truncate .= $line_matchings[1];
        }
        // calculate the length of the plain text part of the line; handle entities as one character
        $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
        if ($total_length+$content_length> $length) {
          // the number of characters which are left
          $left = $length - $total_length;
          $entities_length = 0;
          // search for html entities
          if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
            // calculate the real length of all entities in the legal range
            foreach ($entities[0] as $entity) {
              if ($entity[1]+1-$entities_length <= $left) {
                $left--;
                $entities_length += strlen($entity[0]);
              } else {
                // no more characters left
                break;
              }
            }
          }
          $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
          // maximum lenght is reached, so get off the loop
          break;
        } else {
          $truncate .= $line_matchings[2];
          $total_length += $content_length;
        }
        // if the maximum length is reached, get off the loop
        if($total_length>= $length) {
          break;
        }
      }
    } else {
      if (strlen($text) <= $length) {
        return $text;
      } else {
        $truncate = substr($text, 0, $length - strlen($ending));
      }
    }
    // if the words shouldn't be cut in the middle...
    if (!$exact) {
      // ...search the last occurance of a space...
      $spacepos = strrpos($truncate, ' ');
      if (isset($spacepos)) {
        // ...and cut the text in this position
        $truncate = substr($truncate, 0, $spacepos);
      }
    }
    // add the defined ending to the text
    $truncate .= $ending;
    if($considerHtml) {
      // close all unclosed html-tags
      foreach ($open_tags as $tag) {
        $truncate .= '</' . $tag . '>';
      }
    }
    return $truncate;
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
   * Split string preserving quoted strings
   * code based on: http://www.php.net/manual/en/function.explode.php#94024
   * @param str String to split
   * @param delim Regexp to use in preg_split
   * @param quoteChr Quote character
   * @param preserve Boolean whether to preserve the quote character or not
   * @return Array
   */
  public static function splitQuoted($str, $delim='/ /', $quoteChr='"', $preserve=false){
    $resArr = array();
    $n = 0;
    $expEncArr = explode($quoteChr, $str);
    foreach($expEncArr as $encItem) {
      if ($n++%2) {
        array_push($resArr, array_pop($resArr) . ($preserve?$quoteChr:'') . $encItem.($preserve?$quoteChr:''));
      }
      else {
        $expDelArr = preg_split($delim, $encItem);
        array_push($resArr, array_pop($resArr) . array_shift($expDelArr));
        $resArr = array_merge($resArr, $expDelArr);
      }
    }
    return $resArr;
  }

  /**
   * Create an excerpt from the given text around the given phrase
   * code based on: http://stackoverflow.com/questions/1292121/how-to-generate-the-snippet-like-generated-by-google-with-php-and-mysql
   */
  function excerpt($text, $phrase, $radius = 100) {
    $phraseLen = strlen($phrase);
    if ($radius < $phraseLen) {
        $radius = $phraseLen;
    }
    $pos = strpos(strtolower($text), strtolower($phrase));

    $startPos = 0;
    if ($pos > $radius) {
      $startPos = $pos - $radius;
    }
    $textLen = strlen($text);

    $endPos = $pos + $phraseLen + $radius;
    if ($endPos >= $textLen) {
      $endPos = $textLen;
    }

    // make sure to cut at spaces
    $firstSpacePos = strpos($text, " ", $startPos);
    $lastSpacePos = strrpos($text, " ", -(strlen($text)-$endPos));

    $excerpt1 = substr($text, $firstSpacePos, $lastSpacePos-$firstSpacePos);

    // remove open tags
    $excerpt = preg_replace('/^[^<]*?>|<[^>]*?$/', '', $excerpt1);
    return $excerpt;
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
