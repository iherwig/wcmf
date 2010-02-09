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

/**
 * This structure encodes the difference between ISO-8859-1 and Windows-1252,
 * as a map from the UTF-8 encoding of some ISO-8859-1 control characters to
 * the UTF-8 encoding of the non-control characters that Windows-1252 places
 * at the equivalent code points. 
 * code from: http://de3.php.net/manual/de/function.utf8-encode.php#45226
 */

$CP1252Map = array(
  "\xc2\x80" => "\xe2\x82\xac", /* EURO SIGN */
  "\xc2\x82" => "\xe2\x80\x9a", /* SINGLE LOW-9 QUOTATION MARK */
  "\xc2\x83" => "\xc6\x92",     /* LATIN SMALL LETTER F WITH HOOK */
  "\xc2\x84" => "\xe2\x80\x9e", /* DOUBLE LOW-9 QUOTATION MARK */
  "\xc2\x85" => "\xe2\x80\xa6", /* HORIZONTAL ELLIPSIS */
  "\xc2\x86" => "\xe2\x80\xa0", /* DAGGER */
  "\xc2\x87" => "\xe2\x80\xa1", /* DOUBLE DAGGER */
  "\xc2\x88" => "\xcb\x86",     /* MODIFIER LETTER CIRCUMFLEX ACCENT */
  "\xc2\x89" => "\xe2\x80\xb0", /* PER MILLE SIGN */
  "\xc2\x8a" => "\xc5\xa0",     /* LATIN CAPITAL LETTER S WITH CARON */
  "\xc2\x8b" => "\xe2\x80\xb9", /* SINGLE LEFT-POINTING ANGLE QUOTATION */
  "\xc2\x8c" => "\xc5\x92",     /* LATIN CAPITAL LIGATURE OE */
  "\xc2\x8e" => "\xc5\xbd",     /* LATIN CAPITAL LETTER Z WITH CARON */
  "\xc2\x91" => "\xe2\x80\x98", /* LEFT SINGLE QUOTATION MARK */
  "\xc2\x92" => "\xe2\x80\x99", /* RIGHT SINGLE QUOTATION MARK */
  "\xc2\x93" => "\xe2\x80\x9c", /* LEFT DOUBLE QUOTATION MARK */
  "\xc2\x94" => "\xe2\x80\x9d", /* RIGHT DOUBLE QUOTATION MARK */
  "\xc2\x95" => "\xe2\x80\xa2", /* BULLET */
  "\xc2\x96" => "\xe2\x80\x93", /* EN DASH */
  "\xc2\x97" => "\xe2\x80\x94", /* EM DASH */

  "\xc2\x98" => "\xcb\x9c",     /* SMALL TILDE */
  "\xc2\x99" => "\xe2\x84\xa2", /* TRADE MARK SIGN */
  "\xc2\x9a" => "\xc5\xa1",     /* LATIN SMALL LETTER S WITH CARON */
  "\xc2\x9b" => "\xe2\x80\xba", /* SINGLE RIGHT-POINTING ANGLE QUOTATION*/
  "\xc2\x9c" => "\xc5\x93",     /* LATIN SMALL LIGATURE OE */
  "\xc2\x9e" => "\xc5\xbe",     /* LATIN SMALL LETTER Z WITH CARON */
  "\xc2\x9f" => "\xc5\xb8"      /* LATIN CAPITAL LETTER Y WITH DIAERESIS*/
);

/**
 * @class EncodingUtil
 * @ingroup Util
 * @brief EncodingUtil provides helper functions for working with different encodings
 * mainly UTF-8.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class EncodingUtil
{
  /**
   * Returns true if the given string is valid UTF-8 and false otherwise.
   * @param string The string to be tested
   * code from: http://us2.php.net/mb_detect_encoding
   */
  function isUtf8($string)
  {
    if ($string === mb_convert_encoding(mb_convert_encoding($string, "UTF-32", "UTF-8"), "UTF-8", "UTF-32"))
      return true;
    else
      return false;
  } 
  /**
   * Decodes mixed CP1252 UTF-8 strings to ISO.
   * @param string The string to be decode
   * code from: http://www.php.net/manual/en/function.utf8-decode.php#47146
   */
  function convertCp1252Utf8ToIso($str)
	{
    global $CP1252Map;
    return utf8_decode(strtr($str, array_flip($CP1252Map)));
  }
  /**
   * Encodes ISO strings to mixed CP1252 UTF-8.
   * @param string The string to be encode
   * code from: http://www.php.net/manual/en/function.utf8-decode.php#47146
   */
  function convertIsoToCp1252Utf8($str)
	{
    global $CP1252Map;
    return strtr(utf8_encode($str), $CP1252Map);
  }
  /**
   * Encodes an ISO-8859-1 mixed variable to UTF-8 (PHP 4, PHP 5 compat)
   * @param input An array, associative or simple
   * @param encodeKeys optional
   * @return utf-8 encoded input
   * code from: http://de3.php.net/utf8_encode
   */
  function utf8EncodeMix($input, $encodeKeys=false)
  {
    global $CP1252Map;
    if(is_array($input))
    {
      $result = array();
      foreach($input as $k => $v)
      {               
        $key = ($encodeKeys) ? EncodingUtil::convertIsoToCp1252Utf8($k) : $k;
        $result[$key] = EncodingUtil::utf8EncodeMix( $v, $encodeKeys);
      }
    }
    else
    {
      if (!is_int($input) && !is_float($input) && !is_bool($input)) {
        $result = EncodingUtil::convertIsoToCp1252Utf8($input);
      } else {
        $result = $input;
      }
    }
    return $result;
  }
}
?>
