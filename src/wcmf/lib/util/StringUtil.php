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
          $string .= get_class($variable).'#'.($id+1).' {...}';
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
   * @param $text String to truncate.
   * @param $length Length of returned string (optional, default: 100)
   * @param $suffix Ending to be appended to the trimmed string (optional, default: …)
   * @param $exact Boolean whether to allow to cut inside a word or not (optional, default: false)
   * @return String
   */
  public static function cropString($text, $length=100, $suffix='…', $exact=false) {
    return self::truncateHTML($text, $length, [
       'ellipsis' => $suffix,
       'wholeWord' => !$exact,
    ]);
  }

  /**
   * Function that truncates (shortens) a given HTML5 string to a max number of characters.
   *
   * @version 1.0.1
   * @license MIT
   * @see https://github.com/jlgrall/truncateHTML Project page for truncateHTML
   *
   * @param int $maxLength the max number of characters. A negative int tells the number of characters to remove from the end of $html.
   * @param string $html the HTML string to truncate.
   * @param array $options (optional) an array of options:
   *     $options = [
   *       'ellipsis' =>        (string) Ellipsis. Default: utf8 ? '…' : '...'
   *       'includeEllipsisLength' =>  (bool) Does $maxLength include the length of ellipsis ? Default: true
   *       'wholeWord' =>        (bool) Truncate at end of last whole word. Default: true
   *       'cutWord' =>        (int>=0|false) Default: 18
   *       'utf8' =>          (bool) Default: true
   *     ]
   * @return string $truncated_html
   */
  public static function truncateHTML($maxLength, $html, array $options = []) {
    assert(is_int($maxLength), "Parameter \$maxLength must be an int");
    assert(is_string($html), "Parameter \$html must be a string");

    $_isUtf8 = !isset($options['utf8']) || $options['utf8'] === true;
    $default = [
        // If utf8, ellipsis defaults to HORIZONTAL ELLIPSIS ('…' ie. '...' as a single unicode character):
        'ellipsis' => $_isUtf8 ? "\xe2\x80\xa6" : '...',
        'includeEllipsisLength' => true,
        'wholeWord' => true,
        'cutWord' => 18,  // Set to 0 or false to disable
        'utf8' => true,

        // Internal use:
        'forceBacktrack' => false,
        'debug' => false,
    ];
    $options += $default;

    assert(is_int($options['cutWord']) || $options['cutWord'] === false, "Option \$options['cutWord'] must be an integer or FALSE");

    // THE function that does all the work of finding the position for the ellipsis,
    // the position for the truncation, and keeping track of opened tags:
    $analyze = function($maxLength, $html, array $options = []) use (&$analyze) {
      // For UTF-8 input:
      $utf8_mod = $options['utf8'] ? 'u' : '';
      $strlen = $options['utf8'] ? 'mb_strlen' : 'strlen';
      $substr = $options['utf8'] ? 'mb_substr' : 'substr';

      if ($maxLength === -1) {
        // Internal use only: in this case, we are only interested in the length of $html, not in really truncating it.
        $maxLength = strlen($html);
        $options = ['ellipsis' => '', 'includeEllipsisLength' => false, 'wholeWord' => false] + $options;
      }

      $pos = 0;      // Current position in $html
      $length = 0;    // Length of $html at $pos (number of countable characters)
      $openedTags = [];  // Stack of opened tags at $pos
      $isCounting = true;  // Are we currently counting the characters we meet ? (false in HTML comments, <script> tags, etc.)
      $textStartPos = 0;  // Start of text in the current tag

      // Each endData stores a possible end:
      // - $endData_maxLength: when we reach $maxLength
      // - $endData_ellipsisIncluded: when we reach $maxLength for (includeEllipsisLength === true)
      // - $endData_lastCountedChar: always keep data about the last counted character before the current tag (used when we need to backtrack to the previous whole word, or when we meet a mismatched closing tag)
      $endData_maxLength = [
          'ellipsisPos' => -1,  // Position where to insert the ellipsis (disabled if set to -2)
          'length' => -1,      // Length of $html at 'ellipsisPos'
          'truncatePos' => -1,  // Position where to truncate the $html (disabled if set to -2)
          'openedTags' => [],    // Stack of opened tags at 'truncatePos'
      ];
      $endData_ellipsisIncluded = $endData_maxLength;
      $endData_lastCountedChar = ['ellipsisPos' => 0, 'length' => 0] + $endData_maxLength;

      // If we need to include the length of the ellipsis:
      if ($options['includeEllipsisLength']) {
        // Compute the length of the ellipsis:
        $ellipsisLength = $strlen($options['ellipsis']);
        if ($ellipsisLength > 1 && $options['ellipsis'] !== '...') {
          $ellipsisLength = $analyze(-1, $options['ellipsis'], $options)['length'];
        }
        // Temporarily reduce $maxLength to include the length of ellipsis:
        // (Because we need to find the position for the ellipsis, but then we will also need to test if we can reach the end of $html without counting the ellipsis)
        $ellipsis_maxLength = $maxLength;
        $maxLength = max($maxLength - $ellipsisLength, 0);
      }
      else {
        // Disable computations for $endData_ellipsisIncluded:
        $endData_ellipsisIncluded['ellipsisPos'] = -2;
        $endData_ellipsisIncluded['truncatePos'] = -2;
      }

      // If we don't need to backtrack:
      if (!$options['wholeWord'] && !$options['forceBacktrack']) {
        // Disable computations for $endData_lastCountedChar:
        $endData_lastCountedChar['ellipsisPos'] = -2;
        $endData_lastCountedChar['truncatePos'] = -2;
      }


      // Regexes to find the next tag in $html:
      $re_inHTML = '/
        <\/?(?!br\b)        # Start of tag, excepted tag "br" (which is considered as a space, not as a tag)
          (\w+|!DOCTYPE)        # Any HTML tag, including "!DOCTYPE"
        [^>]*>          # Attributes and end of tag
      | <!--            # HTML comment tag
    /isx'.$utf8_mod;
      $re_inComment = '/-->/s'.$utf8_mod;      // Only used when we are inside a HTML comment
      $re_inScript = '/<\/script>/is'.$utf8_mod;  // Only used when we are inside a <script> tag
      $re_inStyle = '/<\/style>/is'.$utf8_mod;  // Only used when we are inside a <style> tag
      $re_nextTag = $re_inHTML;          // Currently used regex to find next tag

      // Regex for multi-characters that count for 1 character:
      $re_mc = '/
        (?:                        # Sequence of more than 1 space
        \s|&nbsp;|<br ?\/?>|&tab;|&newline;
        ){2,}
      | &[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};  # HTML entity
    /isx'.$utf8_mod;

      // Part of a regex that finds separators between whole words:
      // (The regex must be fixed-length, ie. no quantifier (required for PCRE negative lookbehind))
      $wholeWordSep = '\s|&nbsp;|<br>|<br\/>|<br \/>|<br >|&tab;|&newline;';

      // If the characters that separates 2 (opening and/or closing) tags are all spaces, we won't count them.
      $chars_onlySpaces = " \t\n\r\f";

      // Self-closing tags (Write them in lowercase!)
      // Source: https://sites.google.com/site/getsnippet/html/html5/list-of-html-self-closing-tags
      // + added: !doctype
      // + added deprecated elements: basefont, frame, isindex
      $selfClosingTags = ['area','base','br','col','command','embed','hr','img','input','keygen','link','meta','param','source','track','wbr','!doctype', 'basefont', 'frame', 'isindex'];

      // Inside those tags, we will set $isCounting to false, and the characters won't be counted:
      $noCountingTags = ['head', 'noscript', 'script', 'style', '!--'];


      /**
       * Proxy to $finalizeEllipsisData().
       * Allows to restore the original $maxLength to continue testing if length of $html fits in $maxLength without the ellipsis.
       */
      $reachedMaxLength = function() use (&$pos, &$length, &$maxLength, &$ellipsis_maxLength, &$endData_maxLength, &$endData_ellipsisIncluded, &$finalizeEllipsisData, &$reachedMaxLength) {
        assert($length === $maxLength, "Only call if \$maxLength is reached. \$pos=$pos, \$maxLength=$maxLength");

        if ($endData_ellipsisIncluded['ellipsisPos'] === -1) {
          $endData_ellipsisIncluded = $finalizeEllipsisData($endData_ellipsisIncluded);
          $maxLength = $ellipsis_maxLength;
          if ($length === $maxLength) $reachedMaxLength();
        }
        elseif ($endData_maxLength['ellipsisPos'] === -1) {
          $endData_maxLength = $finalizeEllipsisData($endData_maxLength);
        }
        else {
          assert(false, "If we call this function multiple times once we reach \$maxLength, there is an error somewhere.");
        }
      };

      /**
       * Each time a countable character is reached, we may need to update the truncate position of the endDatas.
       *
       * @return bool false if final truncate position reached
       */
      $reachedCountableChar = function() use (&$pos, &$openedTags, &$endData_maxLength, &$endData_ellipsisIncluded, &$endData_lastCountedChar) {
        if ($endData_lastCountedChar['truncatePos'] === -1) {
          $endData_lastCountedChar['truncatePos'] = $pos;
          $endData_lastCountedChar['openedTags'] = $openedTags;
        }
        if ($endData_ellipsisIncluded['ellipsisPos'] >= 0 && $endData_ellipsisIncluded['truncatePos'] === -1) {
          $endData_ellipsisIncluded['truncatePos'] = $pos;
          $endData_ellipsisIncluded['openedTags'] = $openedTags;
        }
        if ($endData_maxLength['ellipsisPos'] >= 0) {
          if ($endData_maxLength['truncatePos'] === -1) {  // truncatePos may already be set in case of wholeWord or backtracking
            $endData_maxLength['truncatePos'] = $pos;
            $endData_maxLength['openedTags'] = $openedTags;
          }
          return false;
        }
        return true;
      };
      $reachedOpenTag = $reachedCountableChar;  // An opening tag will also end the truncation after the ellipsis is found.

      /**
       * Finalize $endData for ellipsis at current $pos.
       * It may set ellipsis to either:
       * - the current $pos
       * - the end of the previous whole word
       * - the last counted character before the current tag ($endData_lastCountedChar)
       *
       * @param array $endData
       * @return array modified $endData
       */
      $finalizeEllipsisData = function(array $endData) use (&$pos, &$length, &$openedTags, &$textStartPos, &$tagPos, &$endData_lastCountedChar, &$wholeWordSep, &$utf8_mod, &$options, &$html, &$analyze, &$strlen) {
        $endData['ellipsisPos'] = $pos;
        $endData['length'] = $length;

        if ($options['wholeWord']) {
          $minPos = $textStartPos;
          $maxPos = $pos - $textStartPos;
          $keepCurrentPos = false;
          if ($pos === $tagPos) $keepCurrentPos = true;

          if (!$keepCurrentPos && $options['cutWord'] > 0 && $pos > $textStartPos) {
            // Here we will find the length of current word and compare it to $options['cutWord'].
            $re_wordStart = "/\G.{0,$maxPos}\K(?<=$wholeWordSep)(?!$wholeWordSep)./s";    // No $utf8_mod, because $maxPos is in bytes.
            $found = preg_match($re_wordStart, $html, $wordStartMatches, PREG_OFFSET_CAPTURE, $minPos);
            if ($found && $wordStartMatches[0][1] > $minPos) {
              $wordStart = $wordStartMatches[0][1];
            }
            else {
              $wordStart = $textStartPos;
            }
            $wordBytes = $pos - $wordStart;
            if ($wordBytes >= $options['cutWord']) {
              $word = substr($html, $wordStart, $wordBytes);
              $wordLength = $analyze(-1, $word, $options)['length'];
              if ($wordLength >= $options['cutWord']) {
                $keepCurrentPos = true;
              }
            }
          }
          if (!$keepCurrentPos) {
            // Here we will find the end of the previous word.
            $re_wholeWord = "/\G.{0,$maxPos}\K(?<!$wholeWordSep)(?=$wholeWordSep)./s";    // No $utf8_mod, because $maxPos is in bytes.
            $found = preg_match($re_wholeWord, $html, $wholeWordMatches, PREG_OFFSET_CAPTURE, $minPos);
            if ($found && $wholeWordMatches[0][1] > 0) {
              $newPos = $wholeWordMatches[0][1];
              assert($newPos <= $pos, "\$newPos can only be moved backward from $\pos (\$newPos=$newPos, \$pos=$pos)");
              if ($newPos !== $pos) {
                $endData['ellipsisPos'] = $newPos;
                $endData['truncatePos'] = $newPos;
                $endData['openedTags'] = $openedTags;
                $removed = substr($html, $newPos, $pos - $newPos);
                $endData['length'] -= $analyze(-1, $removed, $options)['length'];
              }
            }
            else {
              // If not found, backtrack to last counted character:
              $endData = $endData_lastCountedChar;
            }
          }
        }
        return $endData;
      };


      // Special case:
      if ($maxLength === 0) $reachedMaxLength();


      // Process each tag of $html:
      while (true) {

        // Find next tag:
        if (preg_match($re_nextTag, $html, $tagMatches, PREG_OFFSET_CAPTURE, $pos)) {
          list($tag, $tagPos) = $tagMatches[0];
          if ($tag === '<!--') $tagMatches[1][0] = '!--';  // Will be used as tagName
        }
        else {
          list($tag, $tagPos) = ['', strlen($html)];
        }

        // Consider the text between current $pos and the next tag:
        $textBytes = $tagPos - $pos;
        $textStartPos = $pos;
        // We don't count characters if the text between 2 tags has only spaces:
        $textHasOnlySpaces = strspn($html, $chars_onlySpaces, $pos, $tagPos) === $textBytes;

        // Count characters to the next tag:
        if ($isCounting && $textBytes > 0 && !$textHasOnlySpaces) {

          // Find and process all multi-characters that count for 1 character:
          $text = substr($html, $textStartPos, $textBytes);
          preg_match_all($re_mc, $text, $mcMatches, PREG_OFFSET_CAPTURE, 0);
          $mcMatches[0][] = ['', $textBytes];
          for ($i = 0, $l = count($mcMatches[0]); $i < $l; $i++) {
            $mcMatch = $mcMatches[0][$i];

            // The sequence before the multi-character:
            $pre_mcMatchBytes = ($textStartPos + $mcMatch[1]) - $pos;
            if ($pre_mcMatchBytes > 0) {
              if (!$reachedCountableChar()) break;
              $pre_mcMatchLength = $strlen(substr($html, $pos, $pre_mcMatchBytes));
              $remainingLength = $maxLength - $length;
              if ($pre_mcMatchLength > $remainingLength) {
                $pos += strlen($substr($html, $pos, $remainingLength));
                $length += $remainingLength;
                if ($length === $maxLength) $reachedMaxLength();
                $i--;
                continue;
              }
              else {
                $pos += $pre_mcMatchBytes;
                $length += $pre_mcMatchLength;
                if ($length === $maxLength) $reachedMaxLength();
              }
            }

            // The multi-character that counts for 1 character:
            if ($mcMatch[0] !== '') {
              if (!$reachedCountableChar()) break;
              $pos += strlen($mcMatch[0]);
              $length += 1;
              if ($length === $maxLength) $reachedMaxLength();
            }
          }

          assert(!($length > $maxLength), "Counted too far : \$length=$length is greater than \$maxLength:$maxLength");

          // If the final truncation position has ben found, break:
          if ($endData_maxLength['truncatePos'] >= 0) break;

          // Keep endData about the last counted character (for backtracking):
          if ($endData_lastCountedChar['ellipsisPos'] !== -2 && $endData_lastCountedChar['length'] !== $length) {
            $endData_lastCountedChar['ellipsisPos'] = $pos;
            $endData_lastCountedChar['truncatePos'] = -1;
            $endData_lastCountedChar['length'] = $length;
          }
        }
        else {
          // Just read without counting:
          $pos += $textBytes;
        }
        assert(!($pos > $tagPos), "Read too far: \$pos=$pos is greater than \$tagPos:$tagPos");


        if ($tag === '') {  // End of $html:
          break;
        }
        elseif ($tag === '-->') {  // End HTML comment:
          $re_nextTag = $re_inHTML;
          $isCounting = true;
        }
        elseif ($tag === '</script>') {  // End script:
          $re_nextTag = $re_inHTML;
          $isCounting = true;
        }
        elseif ($tag === '</style>') {  // End script:
          $re_nextTag = $re_inHTML;
          $isCounting = true;
        }
        else {  // Other tag:
          $tagName = strtolower($tagMatches[1][0]);

          // Opening tag:
          if ($tag[1] !== '/') {
            $isCountingTag = $isCounting && !in_array($tagName, $noCountingTags, true);
            if (!$reachedOpenTag()) break;

            // If not self-closing tag:
            if ($tag[strlen($tag) - 2] !== '/' && !in_array($tagName, $selfClosingTags, true)) {
              if ($tagName === '!--') {  // Start HTML comment:
                $re_nextTag = $re_inComment;
              }
              elseif ($tagName === 'script') {  // Start script:
                $re_nextTag = $re_inScript;
              }
              elseif ($tagName === 'style') {  // Start style:
                $re_nextTag = $re_inStyle;
              }
              else {
                // Stack opened tag:
                $openedTags[] = ['name' => $tagName, 'wasCounting' => $isCounting];
              }
              $isCounting = $isCountingTag;
            }
          }
          // Closing tag:
          else {
            $prevTag = array_pop($openedTags);

            if ($tagName === $prevTag['name']) {
              $isCounting = $prevTag['wasCounting'];
            }
            else {  // Un-paired closing tag (Malformed HTML ? Mismatched or badly nested tag ?)
              if ($prevTag !== null) $openedTags[] = $prevTag;
              if ($options['debug'] === true) throw new \Exception("Unmatched closing tag '$tag' (\$tagPos=$tagPos, \$pos=$pos, \$length=$length)");
              else {
                // We backtrack:
                if ($endData_lastCountedChar['ellipsisPos'] !== -2) {
                  $endData_maxLength = $endData_lastCountedChar;
                  break;
                }
                // If we cannot backtrack directly, we rerun analyze() and force backtracking:
                else {
                  $maxLength = ($endData_ellipsisIncluded['ellipsisPos'] === -1) ? $ellipsis_maxLength : $maxLength;
                  return $analyze($maxLength, $html, ['forceBacktrack' => true] + $options);
                }
              }
            }
          }
        }

        // Continue after the tag:
        $pos += strlen($tag);
      }

      // Complete endDatas if needed with the current $pos:
      foreach ([&$endData_maxLength, &$endData_ellipsisIncluded] as &$endData) {
        if ($endData['ellipsisPos'] === -1) {  // ie. we didn't reach $maxLength
          // So we can include all the length to $pos:
          $endData['ellipsisPos'] = $pos;
          $endData['length'] = $length;
        }
        if ($endData['truncatePos'] === -1) {  // ie. we didn't reach a countable character after $maxLength
          // So we can include all the bytes to $pos:
          $endData['truncatePos'] = $pos;
          $endData['openedTags'] = $openedTags;
        }
      }

      // Should we return $endData_maxLength or $endData_ellipsisIncluded ?
      // In case we must include the ellipsis length:
      // - if we could reach the end of $html, it means that without the added length of the ellipsis, the length of $html is less than $maxLength
      // - otherwise we return the end with the ellipsis length included
      $endData_selected = $endData_maxLength;
      if ($options['includeEllipsisLength'] && $endData_maxLength['truncatePos'] !== strlen($html)) {
        $endData_selected = $endData_ellipsisIncluded;
      }

      return $endData_selected;
    };  // End of analyze()


    // If $maxLength is negative, remove $maxLength countable characters from the end of the $html:
    if ($maxLength < 0) {
      $maxLength = $analyze(-1, $html, $options)['length'] + $maxLength;
      if ($maxLength < 0) $maxLength = 0;
    }

    // Analyze $html:
    $r = $analyze($maxLength, $html, $options);
    $ellipsisPos = $r['ellipsisPos'];
    $truncatePos = $r['truncatePos'];
    $openedTags = $r['openedTags'];

    assert(!($ellipsisPos < 0), "Not counted: \$ellipsisPos=$ellipsisPos");
    assert(!($truncatePos < 0), "Not processed: \$truncatePos=$truncatePos");
    assert(!($truncatePos > strlen($html)), "Read too far: \$truncatePos=$truncatePos is greater than strlen(\$html)=".strlen($html));

    // If $html is shorter than $maxLength:
    if ($truncatePos === strlen($html)) return $html;

    // Close all remaining opened tags:
    $closingTags = '';
    while (!empty($openedTags))  $closingTags .= '</'.array_pop($openedTags)['name'].'>';

    // Return truncated $html with insertion of ellipsis and appended closing tags:
    return substr($html, 0, $ellipsisPos)
    . $options['ellipsis']
    . substr($html, $ellipsisPos, $truncatePos - $ellipsisPos)
    . $closingTags;
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
