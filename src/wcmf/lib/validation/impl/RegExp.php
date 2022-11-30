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

use wcmf\lib\config\ConfigurationException;
use wcmf\lib\validation\ValidateType;

/**
 * RegExp validates against the given regular expression.
 *
 * Configuration example:
 * @code
 * // integer or empty
 * regexp:{"pattern":"/^[0-9]*$/"}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RegExp implements ValidateType {

  /**
   * @see ValidateType::validate
   * $options is an associative array with key 'pattern'
   */
  public function validate($value, $options=null, $context=null) {
    if (!isset($options['pattern'])) {
      throw new ConfigurationException("No 'pattern' given in regexp options: ".json_encode($options));
    }
    if (!is_array($value)) {
      return preg_match($options['pattern'], $value ?? '');
    }
    foreach ($value as $single) {
      if (!preg_match($options['pattern'], $single)) {
        return false;
      }
    }
    return true;
  }
}
?>
