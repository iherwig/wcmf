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
 * ValueList validates against a list of values.
 *
 * Configuration example:
 * @code
 * // integer or empty
 * list:{"values":"value1,value2,value3","separator":","}
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class ValueList implements ValidateType {
  const DEFAULT_SEPARATOR = ',';

  /**
   * @see ValidateType::validate
   * $options is an associative array with keys 'values' and 'separator'
   */
  public function validate($value, $options=null, $context=null) {
    if (!isset($options['values'])) {
      throw new ConfigurationException("No 'values' given in regexp options: ".json_encode($options));
    }
    if (!is_array($value)) {
      $separator = isset($options['separator']) ? $options['separator'] : self::DEFAULT_SEPARATOR;
      $value = explode($separator, $value);
    }
    $allValues = explode(self::DEFAULT_SEPARATOR, $options['values']);
    foreach ($value as $single) {
      if (!in_array($single, $allValues)) {
        return false;
      }
    }
    return true;
  }
}
?>
