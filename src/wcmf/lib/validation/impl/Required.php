<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2016 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\validation\impl;

use wcmf\lib\validation\ValidateType;

/**
 * Required checks if the value is not empty.
 *
 * Configuration examples:
 * @code
 * required
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class Required implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null, $context=null) {
    return is_array($value) && count($value) > 0 || strlen($value) > 0;
  }
}
?>
