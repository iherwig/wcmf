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
require_once(BASE."wcmf/lib/util/class.EncodingUtil.php");

/**
 * @class JSONUtil
 * @ingroup Util
 * @brief JSONUtil provides helper functions for JSON.
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class JSONUtil
{
  /**
   * Decode a value
   * @param value The value to decode
   * @param assoc True/False, wether to convert objects into associative arrays or not [default: false]
   * @return The decoded value
   */
  static function decode($value, $assoc=false)
  {
    if (is_scalar($value) === true)
    {
      if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
      }
      $result = json_decode($value, $assoc);
      if ($result !== null) {
        return $result;
      }
    }
    return $value;
  }
  
  /**
   * Encode a value
   * @param value The value to encode
   * @return The encoded value
   */
  static function encode($value)
  {
    if (!is_int($value) && !is_float($value) && !is_bool($value)) {
      $value = EncodingUtil::utf8EncodeMix($value);
    }
    return json_encode($value);
  }
}
?>
