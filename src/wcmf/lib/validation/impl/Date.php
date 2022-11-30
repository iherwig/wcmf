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
namespace wcmf\lib\validation\impl;

use wcmf\lib\validation\ValidateType;

/**
 * Date validates against the specified date format.
 *
 * Configuration examples:
 * @code
 * // validate against default format (Y-m-d)
 * date
 *
 * // validate against j-M-Y format (e.g. 15-Feb-2009)
 * date:{"format":"j-M-Y"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Date implements ValidateType {

  const DEFAULT_FORMAT = 'Y-m-d';

  /**
   * @see ValidateType::validate
   * $options is an associative array with keys 'format' (optional)
   */
  public function validate($value, $options=null, $context=null) {
    $format = isset($options['format']) ? $options['format'] : self::DEFAULT_FORMAT;
    return strlen($value ?? '') === 0 || (\DateTime::createFromFormat($format, $value) !== false);
  }
}
?>
