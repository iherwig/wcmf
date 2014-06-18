<?php
/**
 * wCMF - wemove Content Management Framework
 * Copyright (C) 2005-2014 wemove digital solutions GmbH
 *
 * Licensed under the terms of the MIT License.
 *
 * See the LICENSE file distributed with this work for
 * additional information.
 */
namespace wcmf\lib\persistence\validator\impl;

use wcmf\lib\persistence\validator\ValidateType;

/**
 * RegExp ValidateType validates against the given regular expression.
 *
 * @code
 * regexp:^[0-9]*$  // integer or empty
 * @endcode
 *
 * @author ingo herwig <ingo@wemove.com>
 */
class RegExp implements ValidateType {

  /**
   * @see ValidateType::validate
   */
  public function validate($value, $options=null) {
    return preg_match("/".$options."/m", $value);
  }
}
?>
